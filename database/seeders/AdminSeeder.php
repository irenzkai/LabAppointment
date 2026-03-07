<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void {
        \App\Models\User::create([
            'name' => 'System Admin',
            'email' => 'admin@lab.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'phone' => '09123456789',
            'birthdate' => '1990-01-01',
            'sex' => 'Male',
            'address' => 'Lab Main Office',
        ]);
    }
}
