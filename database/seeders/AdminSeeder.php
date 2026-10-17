<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AppointmentConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // =========================================================================
        // 1. SYSTEM ADMINISTRATORS (2 ACCOUNTS)
        // =========================================================================
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'GABRIEL',
                'middle_name' => 'TANTOCO',
                'last_name' => 'MENDOZA',
                'suffix' => null,
                'password' => Hash::make('1@23qweASD'),
                'role' => 'admin',
                'phone' => '09398510466',
                'birthdate' => '1985-04-18',
                'sex' => 'Male',
                'street' => 'BLOCK 4 LOT 15 SAMPAGUITA ST.',
                'barangay' => 'DADIANGAS SOUTH',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin2@gmail.com'],
            [
                'first_name' => 'BEATRICE',
                'middle_name' => 'LORENZO',
                'last_name' => 'ALCANTARA',
                'suffix' => null,
                'password' => Hash::make('1@23qweASD'),
                'role' => 'admin',
                'phone' => '09399510465',
                'birthdate' => '1989-11-24',
                'sex' => 'Female',
                'street' => 'PHASE 2 BLOCK 8 SUNFLOWER AVE.',
                'barangay' => 'DADIANGAS NORTH',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // =========================================================================
        // 2. CLINICAL RECEPTION & ADMISSIONS STAFF (2 ACCOUNTS)
        // =========================================================================
        User::updateOrCreate(
            ['email' => 'staff@gmail.com'],
            [
                'first_name' => 'KRISTINE',
                'middle_name' => 'VALDEZ',
                'last_name' => 'SALVADOR',
                'suffix' => null,
                'password' => Hash::make('1@23qweASD'),
                'role' => 'staff',
                'phone' => '09112223334',
                'birthdate' => '1995-05-15',
                'sex' => 'Female',
                'street' => 'PUROK 7 BANTISIL ROAD',
                'barangay' => 'BANTISIL',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff2@gmail.com'],
            [
                'first_name' => 'MARCO',
                'middle_name' => 'DELFIN',
                'last_name' => 'VILLANUEVA',
                'suffix' => null,
                'password' => Hash::make('1@23qweASD'),
                'role' => 'staff',
                'phone' => '09112223335',
                'birthdate' => '1997-08-20',
                'sex' => 'Male',
                'street' => 'DOOR 3 PIONEER RESIDENCES, PIONEER AVE.',
                'barangay' => 'DADIANGAS EAST',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // =========================================================================
        // 3. REGISTERED MEDICAL TECHNOLOGISTS / LAB TECHS (2 ACCOUNTS)
        // =========================================================================
        User::updateOrCreate(
            ['email' => 'labtech1@gmail.com'],
            [
                'first_name' => 'CHRISTIAN',
                'middle_name' => 'AQUINO',
                'last_name' => 'BERNARDO',
                'suffix' => null,
                'password' => Hash::make('1@23qweASD'),
                'role' => 'lab_tech',
                'phone' => '09112224334',
                'birthdate' => '1994-05-15',
                'sex' => 'Male',
                'street' => '124 APITONG STREET',
                'barangay' => 'DADIANGAS WEST',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'labtech2@gmail.com'],
            [
                'first_name' => 'PATRICIA MAE',
                'middle_name' => 'CORTEZ',
                'last_name' => 'NAVARRO',
                'suffix' => null,
                'password' => Hash::make('1@23qweASD'),
                'role' => 'lab_tech',
                'phone' => '09113223334',
                'birthdate' => '1999-09-12',
                'sex' => 'Female',
                'street' => 'LOT 19 BLOCK 2 SAN ISIDRO ROAD',
                'barangay' => 'SAN ISIDRO',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // =========================================================================
        // 4. PATIENT REGISTRATIONS (4 ACCOUNTS)
        // =========================================================================
        User::updateOrCreate(
            ['email' => 'patient@gmail.com'],
            [
                'first_name' => 'JUAN',
                'middle_name' => 'DELA',
                'last_name' => 'CRUZ',
                'suffix' => null,
                'password' => Hash::make('1@23qweASD'),
                'role' => 'user',
                'phone' => '09445556667',
                'birthdate' => '2000-12-25',
                'sex' => 'Male',
                'street' => 'APITONG STREET',
                'barangay' => 'DADIANGAS WEST',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // Demonstrating Spanish-Filipino "Ñ" character support inside database seeder
        User::updateOrCreate(
            ['email' => 'patient2@gmail.com'],
            [
                'first_name' => 'MARIA CLARA',
                'middle_name' => 'MERCADO',
                'last_name' => 'PEÑARANDA',
                'suffix' => null,
                'password' => Hash::make('1@23qweASD'),
                'role' => 'user',
                'phone' => '09445556668',
                'birthdate' => '1998-03-15',
                'sex' => 'Female',
                'street' => 'LAGUNA ROAD',
                'barangay' => 'CITY HEIGHTS',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // Demonstrating Suffix "JR" support inside database seeder
        User::updateOrCreate(
            ['email' => 'patient3@gmail.com'],
            [
                'first_name' => 'PEDRO',
                'middle_name' => 'SANTOS',
                'last_name' => 'PENDUKO',
                'suffix' => 'JR',
                'password' => Hash::make('1@23qweASD'),
                'role' => 'user',
                'phone' => '09445556669',
                'birthdate' => '1995-07-22',
                'sex' => 'Male',
                'street' => 'ROXAS STREET',
                'barangay' => 'LAGAO',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // Demonstrating Hyphenated Married Surname support inside database seeder
        User::updateOrCreate(
            ['email' => 'patient4@gmail.com'],
            [
                'first_name' => 'ANA',
                'middle_name' => 'REYES',
                'last_name' => 'SILANG-SANTOS',
                'suffix' => null,
                'password' => Hash::make('1@23qweASD'),
                'role' => 'user',
                'phone' => '09445556670',
                'birthdate' => '1996-11-30',
                'sex' => 'Female',
                'street' => 'MABINI STREET',
                'barangay' => 'DADIANGAS WEST',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );

        // =========================================================================
        // 5. DEFAULT CLINIC APPOINTMENT CONFIGURATIONS (WEEKLY SCHEDULE)
        // =========================================================================
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach ($days as $index => $day) {
            AppointmentConfig::updateOrCreate(
                ['day_of_week' => $index],
                [
                    'is_open' => ($index == 0) ? false : true, // Sunday closed
                    'opening_time' => '08:00:00',
                    'closing_time' => '17:00:00',
                    'slot_duration' => 60,
                    'max_patients_per_slot' => 2,
                    'has_lunch_break' => true,
                    'lunch_start' => '12:00:00',
                    'lunch_end' => '13:00:00',
                    'lead_time_hours' => 2, // Standard 2-hour lead time
                ]
            );
        }
    }
}