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
        Schema::create('tree_health_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tree_id')->constrained('trees')->onDelete('cascade');
            $table->date('tanggal_pemeriksaan');
            $table->enum('status_kesehatan', ['Sehat', 'Stres', 'Terinfeksi', 'Mati']);
            $table->text('gejala')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('tindakan_penanganan')->nullable();
            $table->text('catatan_tambahan')->nullable();
            $table->string('foto_kondisi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tree_health_profiles');
    }
};
