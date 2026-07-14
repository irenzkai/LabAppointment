<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentConfig;
use App\Models\Service;
use App\Models\User; 
use App\Models\PaymentProvider;
use App\Notifications\AppointmentNotification; 
use App\Imports\BulkAppointmentImport;
use App\Exports\BulkTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; 
use Maatwebsite\Excel\Facades\Excel;

class BulkAppointmentController extends Controller
{
    /**
     * View Bulk Appointments Wizard Page.
     */
    public function index() 
    {
        $services = Service::where('is_available', true)->get();
        $configs = AppointmentConfig::all()->keyBy('day_of_week');
        $paymentProviders = PaymentProvider::where('is_active', true)->get();

        return view('appointments.bulk', compact('services', 'configs', 'paymentProviders'));
    }

    /**
     * Store manual spreadsheet compiled entries.
     */
    public function storeManual(Request $request) 
    {
        // FIXED: Added bulk receipt file validation rules
        $request->validate([
            'organization_name' => 'required|string|max:255',
            'appointment_date' => 'required|date',
            'payment_method' => 'required|string|in:Cash,Cashless',
            'payment_receipt' => 'required_if:payment_method,Cashless|nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'patients' => 'required|array|min:1',
            'patients.*.name' => 'required|string',
            'patients.*.time_slot' => 'required',
            'patients.*.service_ids' => 'required|array|min:1',
        ], [
            'patients.*.service_ids.required' => 'One or more patients are missing test selections.',
        ]);

        $dayNum = date('w', strtotime($request->appointment_date));
        $config = AppointmentConfig::where('day_of_week', $dayNum)->first();
        $limit = $config->max_patients_per_slot ?? 1;

        // 1. Capacity Check
        $submittedCounts = collect($request->patients)->groupBy('time_slot')->map->count();
        foreach ($submittedCounts as $slot => $count) {
            $existingCount = Appointment::where('appointment_date', $request->appointment_date)
                ->where('time_slot', $slot)
                ->whereIn('status', ['pending', 'approved'])
                ->count();

            if (($existingCount + $count) > $limit) {
                $formattedTime = date('h:i A', strtotime($slot));
                return back()->withInput()->withErrors(['error' => "The {$formattedTime} slot exceeds the limit of {$limit} patients."]);
            }
        }

        $batchId = Str::random(10);

        // FIXED: Handle and cache uploaded bulk payment receipt path
        $receiptPath = null;
        if ($request->hasFile('payment_receipt') && $request->file('payment_receipt')->isValid()) {
            $receiptPath = $request->file('payment_receipt')->store('receipts', 'public');
        }

        // 2. Database Transaction
        DB::beginTransaction();
        try {
            foreach ($request->patients as $p) {
                // Decompose composite name to 1NF columns
                $nameParts = explode(' ', trim($p['name']));
                $firstName = $nameParts[0];
                $lastName = end($nameParts);
                $middleName = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : 'N/A';

                $appointment = Appointment::create([
                    'user_id' => auth()->id(),
                    'organization_name' => $request->organization_name,
                    'batch_id' => $batchId,
                    'appointment_date' => $request->appointment_date,
                    'time_slot' => $p['time_slot'],
                    'patient_first_name' => strtoupper($firstName),
                    'patient_middle_name' => strtoupper($middleName),
                    'patient_last_name' => strtoupper($lastName),
                    'patient_name' => strtoupper($p['name']),
                    'patient_email' => $p['email'],
                    'patient_phone' => $p['phone'], 
                    'patient_sex' => $p['sex'],
                    'patient_birthdate' => $p['birthdate'],
                    'patient_street' => strtoupper($p['address']),
                    'patient_barangay' => 'N/A',
                    'patient_city' => 'N/A',
                    'patient_province' => 'N/A',
                    'payment_method' => $request->payment_method,
                    'payment_receipt' => $receiptPath, // Linked receipt path across all batch records
                    'payment_status' => 'unpaid',
                    'status' => 'pending'
                ]);

                if (!empty($p['service_ids'])) {
                    $appointment->services()->attach($p['service_ids']);
                }
            }

            // 3. Notify Staff
            $staffMembers = User::whereIn('role', ['staff', 'admin'])->get();
            foreach ($staffMembers as $staff) {
                $staff->notify(new AppointmentNotification([
                    'title' => 'New Bulk Request',
                    'message' => "{$request->organization_name} submitted " . count($request->patients) . " patients.",
                    'url' => route('appointments.index'),
                    'type' => 'info'
                ]));
            }

            DB::commit();
            return redirect()->route('appointments.index')->with('success', 'Bulk appointments recorded successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            if ($receiptPath) {
                Storage::disk('public')->delete($receiptPath);
            }
            dd("DATABASE ERROR: " . $e->getMessage(), "LINE: " . $e->getLine()); 
        }
    }

    /**
     * Store Excel import records.
     */
    public function storeExcel(Request $request) 
    {
        $request->validate([
            'organization_name' => 'required',
            'appointment_date' => 'required|date',
            'excel_file' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new BulkAppointmentImport($request->all()), $request->file('excel_file'));

        return redirect()->route('appointments.index')->with('success', 'Excel data imported successfully!');
    }

    /**
     * Parse Excel to Array for live table rendering.
     */
    public function parseExcel(Request $request) 
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            $data = Excel::toArray(new BulkAppointmentImport, $request->file('excel_file'));

            $patients = collect($data[0])->filter(function($row) {
                return !empty($row['name']);
            })->values();

            return response()->json($patients);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not read file. Check headers.'], 500);
        }
    }

    /**
     * Download refined XLSX or CSV Bio-data template compatible with default Excel behaviors.
     */
    public function downloadTemplate($type = 'csv') 
    {
        $columns = ['name', 'birthdate', 'sex', 'phone', 'email', 'address'];
        $filename = "medscreen_template." . $type;
        $sample = ['Juan Dela Cruz', '1990-01-01', 'Male', '09123456789', 'juan@gmail.com', 'Gensan City'];

        if ($type == 'xlsx') {
            return Excel::download(new BulkTemplateExport($columns, $sample), $filename);
        }

        $callback = function() use($columns, $sample) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns);
            fputcsv($file, $sample);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type" => "text/csv; charset=UTF-8", 
            "Content-Disposition" => "attachment; filename=$filename"
        ]);
    }
}