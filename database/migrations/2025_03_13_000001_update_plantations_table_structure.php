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
        // 1. Hapus kolom text geometry dan description dari migrasi sebelumnya
        if (Schema::hasColumns('plantations', ['geometry', 'description'])) {
            Schema::table('plantations', function (Blueprint $table) {
                $table->dropColumn(['geometry', 'description']);
            });
        }

        // 2. Hapus kolom climate_zone dan rename location menjadi geometry
        Schema::table('plantations', function (Blueprint $table) {
            if (Schema::hasColumn('plantations', 'climate_zone')) {
                $table->dropColumn('climate_zone');
            }

            // Rename location menjadi geometry
            if (Schema::hasColumn('plantations', 'location')) {
                $table->renameColumn('location', 'geometry');
            }
        });

        // 3. Tambah kolom baru
        Schema::table('plantations', function (Blueprint $table) {
            // Tambah kolom latitude dan longitude
            $table->decimal('latitude', 10, 8)->nullable()->after('geometry')->comment('Titik tengah latitude dari geometri');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude')->comment('Titik tengah longitude dari geometri');

            // Rename kolom yang ada
            $table->renameColumn('area_size', 'luas_area');
            $table->renameColumn('soil_type', 'tipe_tanah');
        });

        // 4. Tambahkan trigger untuk mengisi latitude dan longitude otomatis
        DB::unprepared('
            CREATE OR REPLACE FUNCTION update_plantation_centroid()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.geometry IS NOT NULL THEN
                    NEW.latitude = ST_Y(ST_Centroid(NEW.geometry));
                    NEW.longitude = ST_X(ST_Centroid(NEW.geometry));
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS plantation_centroid_trigger ON plantations;

            CREATE TRIGGER plantation_centroid_trigger
            BEFORE INSERT OR UPDATE OF geometry
            ON plantations
            FOR EACH ROW
            EXECUTE FUNCTION update_plantation_centroid();
        ');

        // Optional: Jika ingin membuat geometry NOT NULL setelah migrasi selesai
        // Schema::table('plantations', function (Blueprint $table) {
        //     DB::statement('ALTER TABLE plantations ALTER COLUMN geometry SET NOT NULL');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Hapus trigger
        DB::unprepared('
            DROP TRIGGER IF EXISTS plantation_centroid_trigger ON plantations;
            DROP FUNCTION IF EXISTS update_plantation_centroid();
        ');

        // 2. Kembalikan struktur tabel ke kondisi sebelumnya
        Schema::table('plantations', function (Blueprint $table) {
            // Hapus kolom latitude dan longitude
            $table->dropColumn(['latitude', 'longitude']);

            // Rename geometry kembali menjadi location
            $table->renameColumn('geometry', 'location');

            // Tambah kolom climate_zone
            $table->string('climate_zone')->nullable();

            // Rename kolom kembali ke nama awal
            $table->renameColumn('luas_area', 'area_size');
            $table->renameColumn('tipe_tanah', 'soil_type');
        });
    }
};
