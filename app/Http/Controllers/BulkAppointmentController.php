<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentConfig;
use App\Models\Service;
use App\Models\User; 
use App\Notifications\AppointmentNotification; 
use App\Imports\BulkAppointmentImport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB; 
use Maatwebsite\Excel\Facades\Excel;

class BulkAppointmentController extends Controller
{
    public function index() {
        $services = Service::where('is_available', true)->get();
        $configs = \App\Models\AppointmentConfig::all()->keyBy('day_of_week');
        return view('appointments.bulk', compact('services', 'configs'));
    }

    public function storeManual(Request $request) {
        $request->validate([
            'organization_name' => 'required|string|max:255',
            'appointment_date' => 'required|date',
            'patients' => 'required|array|min:1',
            'patients.*.name' => 'required|string',
            'patients.*.time_slot' => 'required',
            // Every patient must have a non-empty array of service IDs
            'patients.*.service_ids' => 'required|array|min:1',
        ], [
            // Custom error message for the user
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
                return back()->withInput()->withErrors(['error' => "The $formattedTime slot exceeds the limit of $limit patients."]);
            }
        }

        $batchId = Str::random(10);

        // 2. Database Transaction
        DB::beginTransaction();
        try {
            foreach ($request->patients as $p) {
                $appointment = Appointment::create([
                    'user_id' => auth()->id(),
                    'organization_name' => $request->organization_name,
                    'batch_id' => $batchId,
                    'appointment_date' => $request->appointment_date,
                    'time_slot' => $p['time_slot'],
                    'patient_name' => $p['name'],
                    'patient_email' => $p['email'],
                    'patient_phone' => $p['phone'], 
                    'patient_sex' => $p['sex'],
                    'patient_birthdate' => $p['birthdate'],
                    'patient_address' => $p['address'],
                    'status' => 'pending'
                ]);
                
                if (!empty($p['service_ids'])) {
                    $appointment->services()->attach($p['service_ids']);
                }
            }

            // 3. Notify Staff (Must happen inside the try block)
            $staffMembers = User::whereIn('role', ['staff', 'admin'])->get();
            foreach($staffMembers as $staff) {
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
            // This will force the error to appear on your screen
            dd("DATABASE ERROR: " . $e->getMessage(), "LINE: " . $e->getLine()); 
        }
    }

    public function storeExcel(Request $request) {
        $request->validate([
            'organization_name' => 'required',
            'appointment_date' => 'required|date',
            'excel_file' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new BulkAppointmentImport($request->all()), $request->file('excel_file'));

        return redirect()->route('appointments.index')->with('success', 'Excel data imported successfully!');
    }

    public function parseExcel(Request $request) {
        // 1. Validate the file
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            // 2. Read the file into an array using your Importer class
            // Note: Make sure BulkAppointmentImport is clean (returns $rows)
            $data = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Imports\BulkAppointmentImport, $request->file('excel_file'));

            // 3. Return the rows (usually in the first sheet [0])
            // Filter out any empty rows
            $patients = collect($data[0])->filter(function($row) {
                return !empty($row['name']);
            })->values();

            return response()->json($patients);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not read file. Check headers.'], 500);
        }
    }

    // Template Download (Bio-data only)
    public function downloadTemplate($type = 'csv') {
        $columns = ['name', 'birthdate', 'sex', 'phone', 'email', 'address'];
        $filename = "medscreen_template." . $type;
        $sample = ['Juan Dela Cruz', '1990-01-01', 'Male', '09123456789', 'juan@gmail.com', 'Gensan City'];

        if ($type == 'xlsx') {
            return \Maatwebsite\Excel\Facades\Excel::download(new class($columns, $sample) implements \Maatwebsite\Excel\Concerns\FromArray {
                protected $c; protected $s;
                public function __construct($c, $s){ $this->c = $c; $this->s = $s; }
                public function array(): array { return [$this->c, $this->s]; }
            }, $filename);
        }

        $callback = function() use($columns, $sample) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $sample);
            fclose($file);
        };
        return response()->stream($callback, 200, ["Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=$filename"]);
    }
}