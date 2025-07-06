<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Kegiatan;

class KegiatanAlterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Memperbarui semua data kegiatan yang memiliki petugas NULL
        DB::table('kegiatan')
            ->whereNull('petugas')
            ->update(['petugas' => '-']);

        $this->command->info('Semua data kegiatan yang petugas-nya NULL telah diperbarui.');
    }
}
