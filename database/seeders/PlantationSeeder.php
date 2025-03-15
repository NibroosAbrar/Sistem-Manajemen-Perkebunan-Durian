<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlantationSeeder extends Seeder
{
    public function run()
    {
        DB::table('plantations')->insert([
            [   'user_id' => 1,
                'name' => 'Kebun Durian Sukabumi',
                'location' => DB::raw("ST_GeomFromText('POINT(106.773971 -7.073748)', 4326)"),
                'area_size' => 60.0, // dalam hektar
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
