<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SuperAdminSeeder extends Seeder {
    public function run(): void {
        User::firstOrCreate(
            ['email' => 'superadmin@durigeo.com'],
            [
                'name' => 'Superadmin',
                'password' => Hash::make('password123'),
                'role' => 'Superadmin'
            ]
        );
    }
}







