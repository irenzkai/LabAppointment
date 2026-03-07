<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'renzmamon2@gmail.com'], // Check if this email exists
            [
                'name' => 'System Admin',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'phone' => '09399510464',
                'birthdate' => '2005-05-05',
                'sex' => 'Male',
                'address' => 'HTC',
            ]
        );
    }
}
