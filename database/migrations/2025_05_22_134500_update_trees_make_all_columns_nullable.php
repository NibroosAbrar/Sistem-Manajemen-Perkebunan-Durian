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
        // Hapus trigger sementara sebelum mengubah struktur kolom
        DB::unprepared('DROP TRIGGER IF EXISTS tree_coordinates_trigger ON trees;');

        Schema::table('trees', function (Blueprint $table) {
            // Ubah kolom non-enum menjadi nullable
            $table->string('varietas')->nullable()->change();
            $table->year('tahun_tanam')->nullable()->change();
            $table->geometry('canopy_geometry')->nullable()->change();
            $table->foreignId('plantation_id')->nullable()->change();
        });

        // Khusus kolom enum, gunakan raw statement untuk PostgreSQL
        DB::statement('ALTER TABLE trees ALTER COLUMN health_status DROP NOT NULL;');
        DB::statement('ALTER TABLE trees ALTER COLUMN fase DROP NOT NULL;');

        // Buat ulang trigger function
        DB::unprepared('
            CREATE OR REPLACE FUNCTION update_tree_coordinates()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Hanya update koordinat jika canopy_geometry ada
                IF NEW.canopy_geometry IS NOT NULL THEN
                    NEW.latitude = ST_Y(ST_Centroid(NEW.canopy_geometry));
                    NEW.longitude = ST_X(ST_Centroid(NEW.canopy_geometry));
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Buat ulang trigger
        DB::unprepared('
            CREATE TRIGGER tree_coordinates_trigger
            BEFORE INSERT OR UPDATE OF canopy_geometry
            ON trees
            FOR EACH ROW
            EXECUTE FUNCTION update_tree_coordinates();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus trigger sementara sebelum mengubah struktur kolom
        DB::unprepared('DROP TRIGGER IF EXISTS tree_coordinates_trigger ON trees;');

        Schema::table('trees', function (Blueprint $table) {
            // Kembalikan kolom non-enum menjadi required
            $table->string('varietas')->nullable(false)->change();
            $table->year('tahun_tanam')->nullable(false)->change();
            $table->geometry('canopy_geometry')->nullable(false)->change();
            $table->foreignId('plantation_id')->nullable(false)->change();
        });

        // Khusus kolom enum, gunakan raw statement untuk PostgreSQL
        DB::statement('ALTER TABLE trees ALTER COLUMN health_status SET NOT NULL;');
        DB::statement('ALTER TABLE trees ALTER COLUMN fase SET NOT NULL;');

        // Buat ulang trigger function seperti semula
        DB::unprepared('
            CREATE OR REPLACE FUNCTION update_tree_coordinates()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.latitude = ST_Y(ST_Centroid(NEW.canopy_geometry));
                NEW.longitude = ST_X(ST_Centroid(NEW.canopy_geometry));
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Buat ulang trigger
        DB::unprepared('
            CREATE TRIGGER tree_coordinates_trigger
            BEFORE INSERT OR UPDATE OF canopy_geometry
            ON trees
            FOR EACH ROW
            EXECUTE FUNCTION update_tree_coordinates();
        ');
    }
}; 