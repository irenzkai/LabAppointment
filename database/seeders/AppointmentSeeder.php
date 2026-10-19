<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Dependent;
use App\Models\Service;
use App\Models\User;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure prerequisite accounts exist
        if (User::where('role', 'user')->count() === 0) {
            $this->call(AdminSeeder::class);
        }

        // 2. Ensure services exist
        if (Service::count() === 0) {
            $this->call(ServiceSeeder::class);
        }

        $patients = User::where('role', 'user')->orderBy('id')->get();
        if ($patients->isEmpty()) {
            return;
        }

        $p1 = $patients[0]; // Juan Dela Cruz
        $p2 = $patients[1] ?? $patients[0]; // Maria Clara Peñaranda
        $p3 = $patients[2] ?? $patients[0]; // Pedro Penduko Jr
        $p4 = $patients[3] ?? $patients[0]; // Ana Silang-Santos

        // 3. Ensure sample minor dependents exist for family bookings
        $dep1 = Dependent::firstOrCreate(
            ['user_id' => $p1->id, 'first_name' => 'JUNIOR', 'last_name' => 'CRUZ'],
            [
                'middle_name' => 'DELA',
                'suffix' => 'JR',
                'birthdate' => Carbon::now()->subYears(10)->format('Y-m-d'),
                'sex' => 'Male',
                'phone' => '09445556667',
                'street' => 'APITONG STREET',
                'barangay' => 'DADIANGAS WEST',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
            ]
        );

        $dep2 = Dependent::firstOrCreate(
            ['user_id' => $p2->id, 'first_name' => 'SOPHIA', 'last_name' => 'PEÑARANDA'],
            [
                'middle_name' => 'MERCADO',
                'suffix' => null,
                'birthdate' => Carbon::now()->subYears(7)->format('Y-m-d'),
                'sex' => 'Female',
                'phone' => '09445556668',
                'street' => 'LAGUNA ROAD',
                'barangay' => 'CITY HEIGHTS',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
            ]
        );

        // 4. Fetch catalog of active clinical services
        $availableServices = Service::where('is_available', true)->get();
        if ($availableServices->isEmpty()) {
            $availableServices = Service::all();
        }

        $fbs = $availableServices->firstWhere('name', 'FBS / RBS') ?? $availableServices->first();
        $bun = $availableServices->firstWhere('name', 'BUN') ?? $fbs;
        $cbc = $availableServices->firstWhere('name', 'CBC with Platelet') ?? $fbs;
        $uri = $availableServices->firstWhere('name', 'Urinalysis') ?? $fbs;
        $lipid = $availableServices->firstWhere('name', 'Lipid Profile Individual') ?? $fbs;

        $servicePool = [$fbs, $bun, $cbc, $uri, $lipid];

        // 5. Helper function to create non-expiring Tested Cash appointments
        $createAppointment = function (
            User $user,
            Service $service,
            Carbon $dateTime,
            array $extras = []
        ) {
            $appDate = $extras['appointment_date'] ?? $dateTime->toDateString();
            $timeSlot = $extras['time_slot'] ?? $dateTime->format('H:i:00');

            $fName = $extras['patient_first_name'] ?? $user->first_name;
            $mName = $extras['patient_middle_name'] ?? ($user->middle_name ?? 'N/A');
            $lName = $extras['patient_last_name'] ?? $user->last_name;
            $suffix = $extras['patient_suffix'] ?? $user->suffix;

            $mid = ($mName && $mName !== 'N/A') ? " {$mName}" : "";
            $suf = $suffix ? " {$suffix}" : "";
            $fullName = strtoupper(trim("{$fName}{$mid} {$lName}{$suf}"));

            $app = new Appointment();
            $app->user_id = $user->id;
            $app->dependent_id = $extras['dependent_id'] ?? null;
            $app->organization_name = $extras['organization_name'] ?? null;

            // Strictly cap batch_id to 10 characters maximum
            $app->batch_id = isset($extras['batch_id']) ? substr((string)$extras['batch_id'], 0, 10) : null;

            $app->appointment_date = $appDate;
            $app->time_slot = $timeSlot;

            $app->patient_first_name = $fName;
            $app->patient_middle_name = $mName;
            $app->patient_last_name = $lName;
            $app->patient_suffix = $suffix;
            $app->patient_name = $fullName;

            $app->patient_email = $extras['patient_email'] ?? $user->email;
            $app->patient_phone = $extras['patient_phone'] ?? ($user->phone ?? '09123456789');
            $app->patient_sex = $extras['patient_sex'] ?? ($user->sex ?? 'Male');
            $app->patient_birthdate = $extras['patient_birthdate'] ?? ($user->birthdate ?? '1995-01-01');

            $app->patient_street = $extras['patient_street'] ?? ($user->street ?? 'PIONEER AVENUE');
            $app->patient_barangay = $extras['patient_barangay'] ?? ($user->barangay ?? 'DADIANGAS WEST');
            $app->patient_city = $extras['patient_city'] ?? ($user->city ?? 'CITY OF GENERAL SANTOS');
            $app->patient_province = $extras['patient_province'] ?? ($user->province ?? 'SOUTH COTABATO');

            // Strictly Cash and Paid: no empty receipts, no pending debt
            $app->payment_method = 'Cash';
            $app->payment_status = 'paid';
            $app->payment_amount = (float)$service->price;

            // Strictly TESTED: Prevents dynamic expiration and requires no dummy PDF attachments
            $app->status = 'tested';
            $app->deleted_by_patient = false;
            $app->return_reason = null;
            $app->cancellation_reason = null;

            // Complete clinical sampling timestamps
            $app->tested_at = $dateTime->copy()->addMinutes(20);
            $app->result_estimated_at = $dateTime->copy()->addHours(2);

            // Historical creation timestamps
            $app->timestamps = false;
            $app->created_at = $dateTime;
            $app->updated_at = $dateTime->copy()->addMinutes(25);
            $app->save();

            // Attach diagnostic service to pivot table
            $app->services()->sync([$service->id]);

            return $app;
        };

        // =========================================================================
        // 1. HISTORICAL RANGE: NOVEMBER 2024 TO JUNE 2026 (At least 2 per month)
        // =========================================================================
        $startMonth = Carbon::create(2024, 11, 1, 8, 0, 0);
        $endMonth = Carbon::create(2026, 6, 1, 8, 0, 0);

        $currentCursor = $startMonth->copy();
        $batchCounter = 10;

        while ($currentCursor->lessThanOrEqualTo($endMonth)) {
            $year = $currentCursor->year;
            $month = $currentCursor->month;

            // --- Entry 1: Personal Appointment (First half of month) ---
            $date1 = Carbon::create($year, $month, 6, 8, 30, 0);
            $svc1 = $servicePool[($year + $month) % count($servicePool)];
            $user1 = $patients[($month) % $patients->count()];
            $createAppointment($user1, $svc1, $date1);

            // --- Entry 2: Family Dependent Appointment (Mid-month) ---
            $date2 = Carbon::create($year, $month, 14, 10, 0, 0);
            $svc2 = $servicePool[($year + $month + 1) % count($servicePool)];
            $isDep1 = ($month % 2 === 0);
            $targetDep = $isDep1 ? $dep1 : $dep2;
            $parentUser = $isDep1 ? $p1 : $p2;

            $createAppointment($parentUser, $svc2, $date2, [
                'dependent_id' => $targetDep->id,
                'patient_first_name' => $targetDep->first_name,
                'patient_middle_name' => $targetDep->middle_name,
                'patient_last_name' => $targetDep->last_name,
                'patient_suffix' => $targetDep->suffix,
                'patient_sex' => $targetDep->sex,
                'patient_birthdate' => $targetDep->birthdate ? Carbon::parse($targetDep->birthdate)->format('Y-m-d') : '2015-05-15',
            ]);

            // --- Entry 3: Personal Appointment (Second half of month) ---
            $date3 = Carbon::create($year, $month, 21, 13, 30, 0);
            $svc3 = $servicePool[($year + $month + 2) % count($servicePool)];
            $user3 = $patients[($month + 1) % $patients->count()];
            $createAppointment($user3, $svc3, $date3);

            // --- Entry 4: Corporate / Bulk Group Appointment (Alternating months) ---
            if ($month % 2 === 1) {
                $batchCode = 'B' . substr($year, -2) . sprintf('%02d', $month) . Str::upper(Str::random(3)); // Max 8 chars
                $date4 = Carbon::create($year, $month, 27, 11, 0, 0);

                $createAppointment($p3, $cbc, $date4, [
                    'organization_name' => 'DOLE PHILIPPINES',
                    'batch_id' => $batchCode,
                    'patient_first_name' => 'ROBERTO',
                    'patient_middle_name' => 'GARCIA',
                    'patient_last_name' => 'VALENCIA',
                    'patient_email' => "valencia{$batchCounter}@dole.com",
                    'patient_phone' => '09171234561',
                ]);

                $createAppointment($p3, $fbs, $date4, [
                    'organization_name' => 'DOLE PHILIPPINES',
                    'batch_id' => $batchCode,
                    'patient_first_name' => 'CARMINA',
                    'patient_middle_name' => 'LOPEZ',
                    'patient_last_name' => 'TAN',
                    'patient_email' => "tan{$batchCounter}@dole.com",
                    'patient_phone' => '09171234562',
                    'patient_sex' => 'Female',
                ]);

                $batchCounter++;
            }

            $currentCursor->addMonth();
        }

        // =========================================================================
        // 2. TODAY'S APPOINTMENTS (Keeps Live Queue, Stats, & Dashboards Populated)
        // =========================================================================
        $today = Carbon::today();

        $appToday1 = $createAppointment($p1, $fbs, $today->copy()->setTime(8, 0, 0));
        ActivityLog::record('TESTED', 'Sampling completed and details verified', $appToday1->patient_name, $appToday1->id);

        $appToday2 = $createAppointment($p2, $bun, $today->copy()->setTime(9, 30, 0));
        $appToday3 = $createAppointment($p3, $cbc, $today->copy()->setTime(11, 0, 0));

        // Today's Family Dependent Booking
        $createAppointment($p1, $uri, $today->copy()->setTime(14, 0, 0), [
            'dependent_id' => $dep1->id,
            'patient_first_name' => $dep1->first_name,
            'patient_middle_name' => $dep1->middle_name,
            'patient_last_name' => $dep1->last_name,
            'patient_suffix' => $dep1->suffix,
            'patient_sex' => $dep1->sex,
            'patient_birthdate' => $dep1->birthdate ? Carbon::parse($dep1->birthdate)->format('Y-m-d') : '2015-05-15',
        ]);
    }
}