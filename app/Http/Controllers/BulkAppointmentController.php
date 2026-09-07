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
use App\Events\QueueUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        // Custom name validation rule ensuring letters, apostrophes, hyphens, and spaces only
        $nameRule = function ($attribute, $value, $fail) {
            $val = trim($value);
            if (empty($val) || $val === 'N/A') return;
            if (!preg_match('/^[a-zA-ZñÑ\s.\'-]+$/u', $val)) {
                $cleanAttr = str_replace(['patients.*.', 'patients.', '_'], ' ', $attribute);
                $fail("The " . trim($cleanAttr) . " may only contain letters, spaces, periods, hyphens, and apostrophes.");
                return;
            }
            if (!preg_match('/^[a-zA-ZñÑ]/u', $val)) {
                $cleanAttr = str_replace(['patients.*.', 'patients.', '_'], ' ', $attribute);
                $fail("The " . trim($cleanAttr) . " must start with a letter.");
                return;
            }
            if (!preg_match('/[a-zA-ZñÑ]/u', $val)) {
                $cleanAttr = str_replace(['patients.*.', 'patients.', '_'], ' ', $attribute);
                $fail("The " . trim($cleanAttr) . " must contain at least one letter.");
                return;
            }
            if (preg_match('/[.\'-]{2,}/u', $val)) {
                $cleanAttr = str_replace(['patients.*.', 'patients.', '_'], ' ', $attribute);
                $fail("The " . trim($cleanAttr) . " cannot contain consecutive punctuation marks.");
                return;
            }
        };

        // Added strict validation rules for patient identity, middle name, suffix, and payments
        $request->validate([
            'organization_name' => 'required|string|max:255',
            'appointment_date' => 'required|date',
            'payment_method' => 'required|string|in:Cash,Cashless',
            'payment_receipt' => 'required_if:payment_method,Cashless|nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'patients' => 'required|array|min:1',
            'patients.*.first_name' => ['nullable', 'string', 'max:60', $nameRule],
            'patients.*.middle_name' => ['nullable', 'string', 'max:60', $nameRule],
            'patients.*.last_name' => ['nullable', 'string', 'max:60', $nameRule],
            'patients.*.suffix' => ['nullable', 'string', 'max:10', 'regex:/^[a-zA-Z\s.]+$/u'],
            'patients.*.name' => 'required|string|max:255',
            'patients.*.sex' => 'nullable|string|in:Male,Female',
            'patients.*.birthdate' => 'nullable|date|before_or_equal:today',
            'patients.*.phone' => ['nullable', 'string', 'regex:/^09\d{9}$/'],
            'patients.*.email' => ['nullable', 'email', 'max:255'],
            'patients.*.time_slot' => 'required',
            'patients.*.service_ids' => 'required|array|min:1',
            'patients.*.street' => 'required|string|max:255',
            'patients.*.barangay' => 'required|string|max:255',
            'patients.*.city' => 'required|string|max:255',
            'patients.*.province' => 'required|string|max:255',
        ], [
            'patients.*.service_ids.required' => 'One or more patients are missing test selections.',
            'patients.*.suffix.regex' => 'The suffix may only contain letters, spaces, and periods (e.g. JR, SR, II, III).',
            'patients.*.phone.regex' => 'Phone numbers must contain exactly 11 digits starting with 09.',
        ]);

        $dayNum = date('w', strtotime($request->appointment_date));
        $config = AppointmentConfig::where('day_of_week', $dayNum)->first();
        $limit = $config->max_patients_per_slot ?? 1;

        // 1. Capacity Check
        $submittedCounts = collect($request->patients)->groupBy('time_slot')->map->count();
        foreach ($submittedCounts as $slot => $count) {
            $existingCount = Appointment::where('appointment_date', $request->appointment_date)
                ->where('time_slot', $slot)
                ->whereIn('status', ['pending', 'approved', 'tested', 'encoded', 'released'])
                ->count();

            if (($existingCount + $count) > $limit) {
                $formattedTime = date('h:i A', strtotime($slot));
                return back()->withInput()->withErrors(['error' => "The {$formattedTime} slot exceeds the limit of {$limit} patients."]);
            }
        }

        $batchId = Str::random(10);

        // Handle and cache uploaded bulk payment receipt path
        $receiptPath = null;
        if ($request->hasFile('payment_receipt') && $request->file('payment_receipt')->isValid()) {
            $receiptPath = $request->file('payment_receipt')->store('receipts', 'public');
        }

        // 2. Database Transaction
        DB::beginTransaction();
        try {
            foreach ($request->patients as $p) {
                $fName = !empty($p['first_name']) ? strtoupper(trim($p['first_name'])) : '';
                $mName = (!empty($p['middle_name']) && strtoupper(trim($p['middle_name'])) !== 'N/A') ? strtoupper(trim($p['middle_name'])) : 'N/A';
                $lName = !empty($p['last_name']) ? strtoupper(trim($p['last_name'])) : '';
                $suffix = !empty($p['suffix']) ? strtoupper(trim($p['suffix'])) : null;

                // Decompose composite name to 1NF columns if individual parts are missing
                if (empty($fName) || empty($lName)) {
                    $nameParts = explode(' ', trim($p['name']));
                    $fName = strtoupper($nameParts[0]);
                    $lName = strtoupper(end($nameParts));
                    $mName = count($nameParts) > 2 ? strtoupper(implode(' ', array_slice($nameParts, 1, -1))) : 'N/A';
                }

                $displayName = !empty($p['name']) ? strtoupper(trim($p['name'])) : trim("{$fName} " . ($mName !== 'N/A' ? "{$mName} " : "") . "{$lName}" . ($suffix ? " {$suffix}" : ""));

                $appointment = Appointment::create([
                    'user_id' => auth()->id(),
                    'organization_name' => strtoupper($request->organization_name),
                    'batch_id' => $batchId,
                    'appointment_date' => $p['appointment_date'] ?? $request->appointment_date,
                    'time_slot' => $p['time_slot'],
                    'patient_first_name' => $fName,
                    'patient_middle_name' => $mName,
                    'patient_last_name' => $lName,
                    'patient_suffix' => $suffix,
                    'patient_name' => $displayName,
                    'patient_email' => $p['email'] ?? null,
                    'patient_phone' => $p['phone'] ?? null,
                    'patient_sex' => $p['sex'] ?? 'Male',
                    'patient_birthdate' => $p['birthdate'] ?? null,
                    'patient_street' => strtoupper($p['street']),
                    'patient_barangay' => strtoupper($p['barangay']),
                    'patient_city' => strtoupper($p['city']),
                    'patient_province' => strtoupper($p['province']),
                    'payment_method' => $request->payment_method,
                    'payment_receipt' => $receiptPath,
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

            // Dispatch live update for the staff/admin queue
            event(new QueueUpdated());

            return redirect()->route('appointments.index')->with('success', 'Bulk appointments recorded successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            if ($receiptPath) {
                Storage::disk('public')->delete($receiptPath);
            }
            return back()->withInput()->withErrors(['error' => 'Database error occurred: ' . $e->getMessage()]);
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

        // Dispatch live update for the staff/admin queue
        event(new QueueUpdated());

        return redirect()->route('appointments.index')->with('success', 'Excel data imported successfully!');
    }

    /**
     * Parse Excel to Array for live table rendering.
     */
    public function parseExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls'
        ]);

        try {
            $data = Excel::toArray(new BulkAppointmentImport, $request->file('excel_file'));
            $patients = collect($data[0] ?? [])->filter(function($row) {
                return !empty($row['name']) || !empty($row['first_name']) || !empty($row[0]);
            })->values();

            return response()->json($patients);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not read file. Please ensure it is a valid Excel spreadsheet.'], 500);
        }
    }

    /**
     * Download refined XLSX Bio-data template with dropdown data validations and cell instructions.
     */
    public function downloadTemplate()
    {
        $filename = "medscreen_bulk_template.xlsx";
        return Excel::download(new BulkTemplateExport(), $filename);
    }
}