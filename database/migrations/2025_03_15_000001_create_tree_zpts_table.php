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
        Schema::create('tree_zpts', function (Blueprint $table) {
            $table->id();
            $table->string('tree_id', 20)->nullable();
            $table->foreign('tree_id')->references('id')->on('trees')->onDelete('cascade');
            $table->date('tanggal_aplikasi')->nullable();
            $table->string('nama_zpt')->nullable();
            $table->string('merek')->nullable();
            $table->enum('jenis_senyawa', ['Alami', 'Sintetis'])->nullable();
            $table->string('konsentrasi')->nullable();
            $table->decimal('volume_larutan', 8, 2)->nullable()->comment('Liter per pohon');
            $table->string('fase_pertumbuhan')->nullable();
            $table->string('metode_aplikasi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tree_zpts');
    }
};
