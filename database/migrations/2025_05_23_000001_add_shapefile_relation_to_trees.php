<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trees', function (Blueprint $table) {
            // Tambahkan kolom shapefile_id sebagai foreign key, nullable agar tidak mengganggu data yang sudah ada
            $table->foreignId('shapefile_id')->nullable()->after('plantation_id')
                  ->constrained('shapefiles')->onDelete('set null');
            
            // Tambahkan kolom polygon_index untuk menyimpan indeks poligon dalam shapefile
            $table->integer('polygon_index')->nullable()->after('shapefile_id')
                  ->comment('Indeks poligon dalam shapefile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trees', function (Blueprint $table) {
            // Hapus foreign key terlebih dahulu
            $table->dropForeign(['shapefile_id']);
            // Kemudian hapus kolom
            $table->dropColumn(['shapefile_id', 'polygon_index']);
        });
    }
}; 