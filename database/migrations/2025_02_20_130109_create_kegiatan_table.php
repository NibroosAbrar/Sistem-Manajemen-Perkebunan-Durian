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
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id(); // Kolom ID
            $table->date('tanggal'); // Kolom tanggal
            $table->enum('jenis_kegiatan', ['Penanaman', 'Pemupukan', 'Pengendalian Hama dan Penyakit']); // Kolom jenis_kegiatan
            $table->text('deskripsi'); // Kolom deskripsi
            $table->string('petugas'); // Kolom petugas
            $table->timestamps(); // Kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
