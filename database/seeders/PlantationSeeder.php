<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlantationSeeder extends Seeder
{
    public function run()
    {
        DB::table('plantations')->insert([
            [
                'user_id' => 1,
                'name' => 'Kebun Durian Sukabumi',
                'geometry' => DB::raw("ST_GeomFromText('POLYGON((106.773971 -7.073748, 106.774971 -7.073748, 106.774971 -7.074748, 106.773971 -7.074748, 106.773971 -7.073748))', 4326)"),
                'luas_area' => 60.0, // dalam hektar
                'tipe_tanah' => 'Latosol',
                'latitude' => -7.073748,
                'longitude' => 106.773971,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}
