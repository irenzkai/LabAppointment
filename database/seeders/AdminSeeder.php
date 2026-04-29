<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'], // Check if this email exists
            [
                'name' => 'System Admin',
                'password' => bcrypt('1@23qweASD'), // Use bcrypt to hash the password
                'role' => 'admin',
                'phone' => '09399510464',
                'birthdate' => '2005-05-05',
                'sex' => 'Male',
                'address' => 'HTC',
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@gmail.com'],
            [
                'name' => 'Staff',
                'password' => bcrypt('password123'), 
                'role' => 'staff',
                'phone' => '09112223334',
                'birthdate' => '1995-05-15',
                'sex' => 'Female',
                'address' => 'Bantisil Street, Gensan'
            ]
        );

        User::updateOrCreate(
            ['email' => 'labtech1@gmail.com'],
            [
                'name' => 'LabTech1',
                'password' => bcrypt('password123'), 
                'role' => 'lab_tech',
                'phone' => '09112224334',
                'birthdate' => '1994-05-15',
                'sex' => 'Male',
                'address' => 'Dadiangas West, Gensan'
            ]
        );

        User::updateOrCreate(
            ['email' => 'labtech2@gmail.com'],
            [
                'name' => 'LabTech2',
                'password' => bcrypt('password123'), 
                'role' => 'lab_tech',
                'phone' => '09113223334',
                'birthdate' => '2001-05-15',
                'sex' => 'Female',
                'address' => 'San Isidro, Gensan'
            ]
        );

        User::updateOrCreate(
            ['email' => 'patient@gmail.com'],
            [
                'name' => 'Juan Dela Cruz',
                'password' => bcrypt('password123'), 
                'role' => 'user',
                'phone' => '09445556667',
                'birthdate' => '2000-12-25',
                'sex' => 'Male',
                'address' => 'Brgy. Dadiangas West, Gensan'
            ]
        );

        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach($days as $index => $day) {
            \App\Models\AppointmentConfig::updateOrCreate(
                ['day_of_week' => $index],
                [
                    'is_open' => ($index == 0) ? false : true,
                    'opening_time' => '08:00:00',
                    'closing_time' => '17:00:00',
                    'slot_duration' => 60,
                    'max_patients_per_slot' => 2,
                    'has_lunch_break' => true,
                    'lunch_start' => '12:00:00',
                    'lunch_end' => '13:00:00'
                ]
            );
        }
    }
}
