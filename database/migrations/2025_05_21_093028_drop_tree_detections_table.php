<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Periksa apakah tabel tree_detections ada sebelum menghapusnya
        if (Schema::hasTable('tree_detections')) {
            // Simpan data yang ada untuk restore jika diperlukan (opsional)
            $treeDetectionsData = DB::table('tree_detections')->get();
            
            // Simpan data ke dalam file atau tabel temporary jika diperlukan
            if (count($treeDetectionsData) > 0) {
                // Contoh: simpan ke dalam cache atau file
                Cache::put('tree_detections_backup', $treeDetectionsData, now()->addDays(7));
            }
            
            // Hapus tabel
            Schema::dropIfExists('tree_detections');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan tabel tree_detections jika belum ada
        if (!Schema::hasTable('tree_detections')) {
            Schema::create('tree_detections', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('aerial_photo_id')->constrained('aerial_photos')->onDelete('cascade');
                $table->foreignId('plantation_id')->constrained('plantations')->onDelete('cascade');
                $table->integer('tree_count')->default(0);
                $table->text('geometry')->nullable(); // Menyimpan geometri hasil deteksi
                $table->boolean('is_processed')->default(false);
                $table->json('detection_data')->nullable(); // Menyimpan metadata hasil deteksi
                $table->timestamps();
            });
            
            // Kembalikan data jika ada backup
            if (Cache::has('tree_detections_backup')) {
                $treeDetectionsData = Cache::get('tree_detections_backup');
                foreach ($treeDetectionsData as $detection) {
                    // Convert object to array and remove any properties not in the table
                    $data = (array) $detection;
                    DB::table('tree_detections')->insert($data);
                }
            }
        }
    }
};
