<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\AppointmentResult;
use App\Models\Dependent;
use App\Models\Service;
use App\Models\User;
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

        $p1 = $patients[0];
        $p2 = $patients[1] ?? $patients[0];
        $p3 = $patients[2] ?? $patients[0];
        $p4 = $patients[3] ?? $patients[0];

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

        // 4. Fetch primary services (e.g., FBS / RBS, BUN, CBC)
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
            string $paymentMethod,
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
            // Strictly cap batch_id to 10 characters max
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

            $app->payment_method = $paymentMethod;
            $app->payment_status = $paymentStatus;
            $app->payment_amount = ($paymentStatus === 'paid') ? (float)$service->price : 0.00;
            $app->status = $status;
            $app->deleted_by_patient = false;

            $app->return_reason = $extras['return_reason'] ?? null;
            $app->cancellation_reason = $extras['cancellation_reason'] ?? null;

            if (in_array($status, ['tested', 'encoded', 'released'])) {
                $app->tested_at = $dateTime->copy()->addMinutes(20);
            }
            if ($status === 'released') {
                $app->results_released_at = $dateTime->copy()->addHours(2);
            }

            // Explicitly set timestamps to match historical queries
            $app->timestamps = false;
            $app->created_at = $dateTime;
            $app->updated_at = $dateTime->copy()->addMinutes(30);
            $app->save();

            // Attach the service
            $app->services()->sync([$service->id]);

            // Add basic result tracker for processed workflows
            if (in_array($status, ['tested', 'encoded', 'released'])) {
                AppointmentResult::firstOrCreate(
                    ['appointment_id' => $app->id],
                    ['included_reports' => ['lab']]
                );
            }

            return $app;
        };

        $now = Carbon::now();
        $today = Carbon::today();

        // =========================================================================
        // 1. TODAY'S APPOINTMENTS (Populates Daily Reports, Today Revenue, Active Queue)
        // =========================================================================

        // Personal Bookings (Self)
        $createAppointment($p1, $fbs, 'pending', 'unpaid', 'Cash', $today->copy()->setTime(8, 0));
        $createAppointment($p2, $bun, 'approved', 'unpaid', 'Cash', $today->copy()->setTime(9, 0));
        $createAppointment($p3, $fbs, 'tested', 'paid', 'Cashless', $today->copy()->setTime(10, 0));
        $createAppointment($p4, $cbc, 'encoded', 'paid', 'Cashless', $today->copy()->setTime(11, 0));
        $createAppointment($p1, $bun, 'retest', 'paid', 'Cash', $today->copy()->setTime(13, 0), [
            'return_reason' => 'Specimen hemolyzed during processing. Recollect required.',
        ]);
        $createAppointment($p2, $fbs, 'canceled', 'paid', 'Cashless', $today->copy()->setTime(14, 0), [
            'cancellation_reason' => 'Patient requested appointment cancellation. Refund pending verification.',
        ]);
        $createAppointment($p3, $cbc, 'released', 'paid', 'Cashless', $today->copy()->setTime(15, 0));

        // Family Dependent Bookings
        $createAppointment($p1, $bun, 'approved', 'paid', 'Cashless', $today->copy()->setTime(9, 30), [
            'dependent_id' => $dep1->id,
            'patient_first_name' => $dep1->first_name,
            'patient_middle_name' => $dep1->middle_name,
            'patient_last_name' => $dep1->last_name,
            'patient_suffix' => $dep1->suffix,
            'patient_sex' => $dep1->sex,
            'patient_birthdate' => $dep1->birthdate,
        ]);

        $createAppointment($p2, $fbs, 'returned', 'unpaid', 'Cash', $today->copy()->setTime(10, 30), [
            'dependent_id' => $dep2->id,
            'patient_first_name' => $dep2->first_name,
            'patient_middle_name' => $dep2->middle_name,
            'patient_last_name' => $dep2->last_name,
            'patient_sex' => $dep2->sex,
            'patient_birthdate' => $dep2->birthdate,
            'return_reason' => 'Referral prescription note unclear. Please upload a legible copy.',
        ]);

        // Bulk / Corporate Batch Bookings (batch_id is strictly <= 10 characters)
        $batchId = 'DOLE' . strtoupper(Str::random(6)); // Exactly 10 chars
        $createAppointment($p3, $bun, 'encoded', 'paid', 'Cashless', $today->copy()->setTime(11, 30), [
            'organization_name' => 'DOLE PHILIPPINES INC.',
            'batch_id' => $batchId,
            'patient_first_name' => 'ROBERTO',
            'patient_middle_name' => 'GARCIA',
            'patient_last_name' => 'VALENCIA',
            'patient_email' => 'r.valencia@dole.com',
            'patient_phone' => '09171234561',
        ]);

        $createAppointment($p3, $fbs, 'encoded', 'paid', 'Cashless', $today->copy()->setTime(11, 30), [
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

            // Active/completed appointments for that day
            $createAppointment($user, $service, 'tested', 'paid', 'Cashless', $pastDay->copy()->setTime(9, 0));
            $createAppointment($user, $bun, 'encoded', 'paid', 'Cash', $pastDay->copy()->setTime(14, 0));

            // Unprogressed appointments scheduled >24 hours ago (These show as EXPIRED)
            if ($d >= 2 && $d <= 4) {
                $createAppointment($user, $fbs, 'pending', 'unpaid', 'Cash', $pastDay->copy()->setTime(8, 0));
            }
        }

        // =========================================================================
        // 3. PAST 6 MONTHS (Monthly Reports & 6-Month Rolling Chart)
        // =========================================================================

        for ($m = 1; $m <= 5; $m++) {
            $monthDate = $now->copy()->subMonths($m)->startOfMonth();

            // Scatter 4-5 appointments across different days of the month
            $daysInMonth = [4, 10, 16, 22, 27];
            foreach ($daysInMonth as $idx => $dayNum) {
                $appTimestamp = $monthDate->copy()->addDays($dayNum)->setTime(10, 0);
                $service = $servicePool[$idx % count($servicePool)];
                $user = $patients[$idx % $patients->count()];

                $statuses = ['tested', 'encoded', 'released', 'canceled'];
                $status = $statuses[$idx % count($statuses)];
                $paymentStatus = ($status === 'canceled') ? 'unpaid' : 'paid';

                $createAppointment(
                    $user,
                    $service,
                    $status,
                    $paymentStatus,
                    ($idx % 2 === 0) ? 'Cashless' : 'Cash',
                    $appTimestamp
                );
            }
        }

        // =========================================================================
        // 4. PAST 4 YEARS (Yearly Reports & 5-Year Analytics Bar Chart)
        // =========================================================================

        for ($y = 1; $y <= 4; $y++) {
            $yearBase = $now->copy()->subYears($y);

            // Seed 4 distributed appointments across quarters of each year
            $quarterMonths = [2, 5, 8, 11];
            foreach ($quarterMonths as $qIdx => $monthNum) {
                $yearTimestamp = $yearBase->copy()->month($monthNum)->day(15)->setTime(10, 0);
                $service = $servicePool[$qIdx % count($servicePool)];
                $user = $patients[$qIdx % $patients->count()];

                $createAppointment(
                    $user,
                    $service,
                    'released',
                    'paid',
                    ($qIdx % 2 === 0) ? 'Cashless' : 'Cash',
                    $yearTimestamp
                );
            }
        }
    }
}