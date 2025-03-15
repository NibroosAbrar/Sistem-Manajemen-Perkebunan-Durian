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
        // Drop existing constraint
        DB::statement('ALTER TABLE kegiatan DROP CONSTRAINT IF EXISTS kegiatan_jenis_kegiatan_check');

        // Add new constraint with 'Panen' option
        DB::statement("ALTER TABLE kegiatan ADD CONSTRAINT kegiatan_jenis_kegiatan_check CHECK (jenis_kegiatan::text = ANY (ARRAY['Penanaman'::text, 'Pemupukan'::text, 'Pengendalian Hama dan Penyakit'::text, 'Panen'::text]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new constraint
        DB::statement('ALTER TABLE kegiatan DROP CONSTRAINT IF EXISTS kegiatan_jenis_kegiatan_check');

        // Restore original constraint without 'Panen' option
        DB::statement("ALTER TABLE kegiatan ADD CONSTRAINT kegiatan_jenis_kegiatan_check CHECK (jenis_kegiatan::text = ANY (ARRAY['Penanaman'::text, 'Pemupukan'::text, 'Pengendalian Hama dan Penyakit'::text]))");
    }
};
