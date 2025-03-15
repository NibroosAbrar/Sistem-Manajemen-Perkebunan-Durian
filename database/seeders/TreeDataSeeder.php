<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreeDataSeeder extends Seeder
{
    public function run()
    {
        // Ambil semua id pohon dari tabel trees
        $trees = DB::table('trees')->select('id')->get();

        foreach ($trees as $tree) {
            // Tambahkan data ke tabel tree_fertilization
            DB::table('tree_fertilization')->insert([
                'tree_id' => $tree->id,
                'nama_pupuk' => null,
                'jenis_pupuk' => null,
                'bentuk_pupuk' => null,
                'dosis_pupuk' => null,
                'sumber_pupuk' => null,
            ]);

            // Tambahkan data ke tabel tree_pesticide
            DB::table('tree_pesticide')->insert([
                'tree_id' => $tree->id,
                'nama_pestisida' => null,
                'jenis_pestisida' => null,
                'dosis' => null,
            ]);

            // Tambahkan data ke tabel harvests
            DB::table('harvests')->insert([
                'tree_id' => $tree->id,
                'fruit_count' => null,
                'total_weight' => null,
                'average_weight_per_fruit' => null,
                'fruit_condition' => null,
            ]);
        }
    }
}
