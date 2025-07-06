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
        // Hapus tabel tree_detections jika ada
        if (Schema::hasTable('tree_detections')) {
            Schema::dropIfExists('tree_detections');
        }

        // Buat ulang tabel tree_detections dengan struktur yang benar
        Schema::create('tree_detections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('shapefile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plantation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('tree_count')->default(0);
            $table->text('geometry')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus ulang tabel
        Schema::dropIfExists('tree_detections');
        
        // Buat kembali dengan struktur lama
        Schema::create('tree_detections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('aerial_photo_id')->constrained('aerial_photos')->onDelete('cascade');
            $table->foreignId('plantation_id')->constrained('plantations')->onDelete('cascade');
            $table->integer('tree_count')->default(0);
            $table->text('geometry')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->json('detection_data')->nullable();
            $table->timestamps();
        });
    }
}; 