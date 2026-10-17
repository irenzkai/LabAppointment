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
        $p2 = $patients[1] ?? $patients[0]; // Maria Clara
        $p3 = $patients[2] ?? $patients[0]; // Pedro Penduko
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

        // 4. Fetch standard laboratory services
        $fbs = Service::where('name', 'like', '%FBS%')->first() ?? Service::first();
        $bun = Service::where('name', 'like', '%BUN%')->first() ?? $fbs;
        $cbc = Service::where('name', 'like', '%CBC%')->first() ?? $fbs;

        $servicePool = [$fbs, $bun, $cbc];

        // 5. Helper function to create appointments with exact historical timestamps
        $createAppointment = function (
            User $user,
            Service $service,
            string $status,
            string $paymentStatus,
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

            // Set to Cash only (prevents blank/missing cashless payment receipts)
            $app->payment_method = 'Cash';
            $app->payment_status = $paymentStatus;
            $app->payment_amount = ($paymentStatus === 'paid') ? (float)$service->price : 0.00;
            $app->status = $status;
            $app->deleted_by_patient = false;

            $app->return_reason = $extras['return_reason'] ?? null;
            $app->cancellation_reason = $extras['cancellation_reason'] ?? null;

            if ($status === 'tested') {
                $app->tested_at = $dateTime->copy()->addMinutes(20);
                $app->result_estimated_at = $dateTime->copy()->addHours(2);
            }

            // Preserve explicit historical timestamp values
            $app->timestamps = false;
            $app->created_at = $dateTime;
            $app->updated_at = $dateTime->copy()->addMinutes(30);
            $app->save();

            // Link the service
            $app->services()->sync([$service->id]);

            return $app;
        };

        $now = Carbon::now();
        $today = Carbon::today();

        // =========================================================================
        // 1. TODAY'S APPOINTMENTS (Populates Today's Metrics, Daily Reports & Queue)
        // =========================================================================

        // Personal Bookings (Self) - Cash Only, No Encoded/Released
        $app1 = $createAppointment($p1, $fbs, 'pending', 'unpaid', $today->copy()->setTime(8, 0));
        ActivityLog::record('BOOKED', 'Appointment booked for testing', $app1->patient_name, $app1->id);

        $createAppointment($p2, $bun, 'approved', 'unpaid', $today->copy()->setTime(9, 0));
        $createAppointment($p3, $fbs, 'tested', 'paid', $today->copy()->setTime(10, 0));
        $createAppointment($p4, $cbc, 'tested', 'paid', $today->copy()->setTime(11, 0));

        $createAppointment($p1, $bun, 'retest', 'paid', $today->copy()->setTime(13, 0), [
            'return_reason' => 'Specimen hemolyzed during collection. Recollection required.',
        ]);

        $createAppointment($p2, $fbs, 'canceled', 'unpaid', $today->copy()->setTime(14, 0), [
            'cancellation_reason' => 'Patient called to cancel due to personal conflict.',
        ]);

        // Family Dependent Bookings (Cash Only)
        $createAppointment($p1, $bun, 'approved', 'paid', $today->copy()->setTime(9, 30), [
            'dependent_id' => $dep1->id,
            'patient_first_name' => $dep1->first_name,
            'patient_middle_name' => $dep1->middle_name,
            'patient_last_name' => $dep1->last_name,
            'patient_suffix' => $dep1->suffix,
            'patient_sex' => $dep1->sex,
            'patient_birthdate' => $dep1->birthdate,
        ]);

        $createAppointment($p2, $fbs, 'returned', 'unpaid', $today->copy()->setTime(10, 30), [
            'dependent_id' => $dep2->id,
            'patient_first_name' => $dep2->first_name,
            'patient_middle_name' => $dep2->middle_name,
            'patient_last_name' => $dep2->last_name,
            'patient_sex' => $dep2->sex,
            'patient_birthdate' => $dep2->birthdate,
            'return_reason' => 'Doctor referral note is missing. Please attach a prescription.',
        ]);

        // Bulk / Corporate Batch Bookings (batch_id is strictly <= 10 characters)
        $batchId = 'DOLE' . strtoupper(Str::random(5)); // Exactly 9 chars
        $createAppointment($p3, $bun, 'tested', 'paid', $today->copy()->setTime(11, 30), [
            'organization_name' => 'DOLE PHILIPPINES INC.',
            'batch_id' => $batchId,
            'patient_first_name' => 'ROBERTO',
            'patient_middle_name' => 'GARCIA',
            'patient_last_name' => 'VALENCIA',
            'patient_email' => 'r.valencia@dole.com',
            'patient_phone' => '09171234561',
        ]);

        $createAppointment($p3, $fbs, 'tested', 'paid', $today->copy()->setTime(11, 30), [
            'organization_name' => 'DOLE PHILIPPINES INC.',
            'batch_id' => $batchId,
            'patient_first_name' => 'CARMINA',
            'patient_middle_name' => 'LOPEZ',
            'patient_last_name' => 'TAN',
            'patient_email' => 'c.tan@dole.com',
            'patient_phone' => '09171234562',
            'patient_sex' => 'Female',
        ]);

        // =========================================================================
        // 2. PAST 7 DAYS (Daily Analytics Chart & Naturally Expired Bookings)
        // =========================================================================

        for ($d = 1; $d <= 6; $d++) {
            $pastDay = $now->copy()->subDays($d);
            $service = $servicePool[$d % count($servicePool)];
            $user = $patients[$d % $patients->count()];

            // Tested appointment (Cash & Paid) - Does NOT expire
            $createAppointment($user, $service, 'tested', 'paid', $pastDay->copy()->setTime(9, 0));

            // Unprogressed appointments scheduled >24 hours ago (These naturally register as EXPIRED)
            if ($d >= 2 && $d <= 4) {
                $createAppointment($user, $fbs, 'pending', 'unpaid', $pastDay->copy()->setTime(8, 0));
            }

            // Returned and canceled historical appointments
            if ($d === 3) {
                $createAppointment($user, $bun, 'returned', 'unpaid', $pastDay->copy()->setTime(13, 0), [
                    'return_reason' => 'Patient did not comply with required 10-hour fasting.',
                ]);
            }
            if ($d === 5) {
                $createAppointment($user, $cbc, 'canceled', 'unpaid', $pastDay->copy()->setTime(15, 0), [
                    'cancellation_reason' => 'Appointment canceled by patient.',
                ]);
            }
        }

        // =========================================================================
        // 3. PAST 6 MONTHS (Monthly Reports & 6-Month Rolling Analytics Chart)
        // =========================================================================

        for ($m = 1; $m <= 5; $m++) {
            $monthDate = $now->copy()->subMonths($m)->startOfMonth();

            // Scatter 4-5 appointments across the month (Cash only, tested/approved/canceled)
            $daysInMonth = [4, 11, 17, 24];
            foreach ($daysInMonth as $idx => $dayNum) {
                $appTimestamp = $monthDate->copy()->addDays($dayNum)->setTime(10, 0);
                $service = $servicePool[$idx % count($servicePool)];
                $user = $patients[$idx % $patients->count()];

                $statuses = ['tested', 'tested', 'approved', 'canceled'];
                $status = $statuses[$idx % count($statuses)];
                $paymentStatus = ($status === 'canceled') ? 'unpaid' : 'paid';

                $createAppointment(
                    $user,
                    $service,
                    $status,
                    $paymentStatus,
                    $appTimestamp
                );
            }

            // Historical corporate batch entry per month (batch_id <= 10 characters)
            $batchCode = 'B' . $m . strtoupper(Str::random(6)); // 8 chars
            $createAppointment($p1, $bun, 'tested', 'paid', $monthDate->copy()->addDays(20)->setTime(9, 0), [
                'organization_name' => 'SAN MIGUEL CORP GSC',
                'batch_id' => $batchCode,
                'patient_first_name' => 'EDUARDO',
                'patient_middle_name' => 'DELA',
                'patient_last_name' => 'ROSA',
                'patient_email' => "eduardo{$m}@smc.com.ph",
            ]);
        }

        // =========================================================================
        // 4. PAST 4 YEARS (Yearly Master Reports & 5-Year Analytics Bar Chart)
        // =========================================================================

        for ($y = 1; $y <= 4; $y++) {
            $yearBase = $now->copy()->subYears($y);

            // Seed 4 distributed appointments across quarters of each year (Tested & Paid Cash)
            $quarterMonths = [2, 5, 8, 11];
            foreach ($quarterMonths as $qIdx => $monthNum) {
                $yearTimestamp = $yearBase->copy()->month($monthNum)->day(15)->setTime(10, 0);
                $service = $servicePool[$qIdx % count($servicePool)];
                $user = $patients[$qIdx % $patients->count()];

                $createAppointment(
                    $user,
                    $service,
                    'tested',
                    'paid',
                    $yearTimestamp
                );
            }
        }
    }
}