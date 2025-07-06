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
        Schema::create('digitasi', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('aerial_photo_id')->constrained('aerial_photos');
            $table->foreignId('plantation_id')->constrained('plantations');
            $table->string('class')->comment('Nama kelas YOLO yang terdeteksi');
            $table->float('confidence')->nullable()->comment('Tingkat keyakinan deteksi YOLO');
            $table->geometry('geom', 'POLYGON', 4326)->comment('Geometri polygon hasil deteksi YOLO');
            $table->boolean('is_processed')->default(true);
            $table->json('detection_meta')->nullable()->comment('Metadata tambahan hasil deteksi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digitasi');
    }
};
