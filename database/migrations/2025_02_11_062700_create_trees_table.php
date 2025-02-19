<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('trees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plantation_id')->constrained('plantations')->onDelete('cascade');
            $table->string('species');
            $table->integer('age');
            $table->enum('health_status', ['Sehat', 'Stres', 'Terinfeksi', 'Mati']);
            $table->float('productivity')->nullable();
            $table->geometry('location'); // Koordinat
            $table->geometry('canopy_geometry'); // Poligon
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('trees');
    }
};

