<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        Schema::create('trees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plantation_id')->constrained('plantations')->onDelete('cascade');
            $table->string('varietas');
            $table->year('tahun_tanam');
            $table->enum('health_status', ['Sehat', 'Stres', 'Terinfeksi', 'Mati']);
            $table->decimal('latitude', 10, 8)->nullable(); // Will be calculated from centroid
            $table->decimal('longitude', 11, 8)->nullable(); // Will be calculated from centroid
            $table->geometry('canopy_geometry'); // Poligon
            $table->string('sumber_bibit')->nullable(); // Added column for source of seed
            $table->timestamps();
        });

        // Create trigger function
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

        // Create trigger
        DB::unprepared('
            CREATE TRIGGER tree_coordinates_trigger
            BEFORE INSERT OR UPDATE OF canopy_geometry
            ON trees
            FOR EACH ROW
            EXECUTE FUNCTION update_tree_coordinates();
        ');
    }

    public function down() {
        // Drop trigger and function
        DB::unprepared('DROP TRIGGER IF EXISTS tree_coordinates_trigger ON trees;');
        DB::unprepared('DROP FUNCTION IF EXISTS update_tree_coordinates;');

        Schema::dropIfExists('trees');
    }
};
