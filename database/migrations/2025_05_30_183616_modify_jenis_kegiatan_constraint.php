<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pertama, hapus constraint enum pada kolom jenis_kegiatan
        DB::statement('ALTER TABLE kegiatan DROP CONSTRAINT IF EXISTS kegiatan_jenis_kegiatan_check');
        
        // Ubah kolom menjadi string biasa
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->string('jenis_kegiatan')->change();
        });
    }

    /**
     * Reverse the migrations.
     * Catatan: Tidak bisa mengembalikan ke enum persis seperti semula,
     * tapi kita bisa menambahkan constraint check yang mirip.
     */
    public function down(): void
    {
        // Tambahkan kembali constraint check untuk jenis_kegiatan
        DB::statement("ALTER TABLE kegiatan ADD CONSTRAINT kegiatan_jenis_kegiatan_check CHECK (jenis_kegiatan::text = ANY (ARRAY['Penanaman'::text, 'Pemupukan'::text, 'Pengendalian Hama dan Penyakit'::text, 'Pestisida'::text, 'Pengatur Tumbuh'::text, 'Pengendalian OPT'::text, 'Panen'::text]))");
    }
};
