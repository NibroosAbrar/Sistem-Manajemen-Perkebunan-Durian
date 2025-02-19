<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('plantations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->float('area_size');
            $table->geometry('location'); // GEOMETRY dengan PostGIS
            $table->string('soil_type')->nullable();
            $table->string('climate_zone')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('plantations');
    }
};

