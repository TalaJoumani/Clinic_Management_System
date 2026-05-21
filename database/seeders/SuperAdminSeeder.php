<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'birth' => null,
            'phone' => '1234567890',
            'gender' => 'male',
            'email' => 'superadmin@gmail.com',
            'role' => 'super_admin',
            'is_verified' => true,
            'password' => Hash::make('password123'),
        ]);
    }
}
