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
        // 1. CREATE SYSTEM ADMINISTRATORS (2 ACCOUNTS)
        // =========================================================================
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'SYSTEM',
                'middle_name' => 'N/A',
                'last_name' => 'ADMIN',
                'password' => Hash::make('1@23qweASD'),
                'role' => 'admin',
                'phone' => '09399510464',
                'birthdate' => '2005-05-05',
                'sex' => 'Male',
                'street' => 'HTC CAMPUS',
                'barangay' => 'DADIANGAS SOUTH',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin2@gmail.com'],
            [
                'first_name' => 'SUPPORT',
                'middle_name' => 'N/A',
                'last_name' => 'ADMIN',
                'password' => Hash::make('1@23qweASD'),
                'role' => 'admin',
                'phone' => '09399510465',
                'birthdate' => '1990-10-10',
                'sex' => 'Female',
                'street' => 'MEDSCREEN OFFICE',
                'barangay' => 'DADIANGAS NORTH',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
            ]
        );

        // =========================================================================
        // 2. CREATE CLINIC STAFF (2 ACCOUNTS)
        // =========================================================================
        User::updateOrCreate(
            ['email' => 'staff@gmail.com'],
            [
                'first_name' => 'CLINIC',
                'middle_name' => 'N/A',
                'last_name' => 'STAFF',
                'password' => Hash::make('1@23qweASD'), 
                'role' => 'staff',
                'phone' => '09112223334',
                'birthdate' => '1995-05-15',
                'sex' => 'Female',
                'street' => 'BANTISIL STREET',
                'barangay' => 'BANTISIL',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff2@gmail.com'],
            [
                'first_name' => 'FRONT',
                'middle_name' => 'DESK',
                'last_name' => 'STAFF',
                'password' => Hash::make('1@23qweASD'), 
                'role' => 'staff',
                'phone' => '09112223335',
                'birthdate' => '1997-08-20',
                'sex' => 'Male',
                'street' => 'PIONEER AVENUE',
                'barangay' => 'DADIANGAS EAST',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
            ]
        );

        // =========================================================================
        // 3. CREATE LABORATORY TECHNICIANS (2 ACCOUNTS)
        // =========================================================================
        User::updateOrCreate(
            ['email' => 'labtech1@gmail.com'],
            [
                'first_name' => 'LAB',
                'middle_name' => 'ONE',
                'last_name' => 'TECH',
                'password' => Hash::make('1@23qweASD'), 
                'role' => 'lab_tech',
                'phone' => '09112224334',
                'birthdate' => '1994-05-15',
                'sex' => 'Male',
                'street' => 'APITONG STREET',
                'barangay' => 'DADIANGAS WEST',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'labtech2@gmail.com'],
            [
                'first_name' => 'LAB',
                'middle_name' => 'TWO',
                'last_name' => 'TECH',
                'password' => Hash::make('1@23qweASD'), 
                'role' => 'lab_tech',
                'phone' => '09113223334',
                'birthdate' => '2001-05-15',
                'sex' => 'Female',
                'street' => 'SAN ISIDRO ROAD',
                'barangay' => 'SAN ISIDRO',
                'city' => 'CITY OF GENERAL SANTOS',
                'province' => 'SOUTH COTABATO',
                'email_verified_at' => now(),
            ]
        );

        // =========================================================================
        // 4. CREATE PATIENTS (4 ACCOUNTS)
        // =========================================================================
        User::updateOrCreate(
            ['email' => 'patient@gmail.com'],
            [
                'first_name' => 'JUAN',
                'middle_name' => 'DELA',
                'last_name' => 'CRUZ',
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
            ]
        );

        User::updateOrCreate(
            ['email' => 'patient2@gmail.com'],
            [
                'first_name' => 'MARIA',
                'middle_name' => 'MERCADO',
                'last_name' => 'CLARA',
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
            ]
        );

        User::updateOrCreate(
            ['email' => 'patient3@gmail.com'],
            [
                'first_name' => 'PEDRO',
                'middle_name' => 'SANTOS',
                'last_name' => 'PENDUKO',
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
            ]
        );

        User::updateOrCreate(
            ['email' => 'patient4@gmail.com'],
            [
                'first_name' => 'ANA',
                'middle_name' => 'REYES',
                'last_name' => 'SILANG',
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
            ]
        );

        // =========================================================================
        // 5. SEED DEFAULT APPOINTMENT CONFIGURATIONS (WEEKLY)
        // =========================================================================
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach($days as $index => $day) {
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
                    'lead_time_hours' => 2 // Standard 2-hour lead time
                ]
            );
        }
    }
}