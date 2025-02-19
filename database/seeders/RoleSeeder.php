<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder {
    public function run() {
        Role::insert([
            ['name' => 'Superadmin'],
            ['name' => 'Manajer'],
            ['name' => 'Operasional'],
            ['name' => 'Guest'],
        ]);
    }
}






