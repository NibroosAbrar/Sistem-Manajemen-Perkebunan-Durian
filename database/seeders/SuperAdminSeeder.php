<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SuperAdminSeeder extends Seeder {
    public function run(): void {
        User::firstOrCreate(
            ['email' => 'superadmin@symadu.com'],
            [
                'name' => 'Superadmin',
                'password' => Hash::make('password123'),
                'role' => 'Superadmin',
                'role_id' => 1,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}







