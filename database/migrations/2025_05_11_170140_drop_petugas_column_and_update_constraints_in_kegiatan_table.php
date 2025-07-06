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
        // Hapus kolom petugas
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropColumn('petugas');
        });

        // Perbarui constraint check pada jenis_kegiatan untuk menerima "Pengatur ZPT"
        DB::statement("ALTER TABLE kegiatan DROP CONSTRAINT IF EXISTS kegiatan_jenis_kegiatan_check");
        DB::statement("ALTER TABLE kegiatan ADD CONSTRAINT kegiatan_jenis_kegiatan_check
                       CHECK (jenis_kegiatan::text = ANY (ARRAY['Penanaman'::character varying,
                                                               'Pemupukan'::character varying,
                                                               'Pengendalian Hama dan Penyakit'::character varying,
                                                               'Panen'::character varying,
                                                               'Pengatur ZPT'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan constraint check ke versi awal (tanpa Pengatur ZPT)
        DB::statement("ALTER TABLE kegiatan DROP CONSTRAINT IF EXISTS kegiatan_jenis_kegiatan_check");
        DB::statement("ALTER TABLE kegiatan ADD CONSTRAINT kegiatan_jenis_kegiatan_check
                       CHECK (jenis_kegiatan::text = ANY (ARRAY['Penanaman'::character varying,
                                                               'Pemupukan'::character varying,
                                                               'Pengendalian Hama dan Penyakit'::character varying,
                                                               'Panen'::character varying]::text[]))");

        // Tambahkan kembali kolom petugas
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->string('petugas')->nullable()->after('deskripsi');
        });
    }
};
