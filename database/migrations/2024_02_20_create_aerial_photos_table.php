<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('aerial_photos', function (Blueprint $table) {
            $table->id();
            $table->string('path')->nullable();
            $table->float('resolution');
            $table->datetime('capture_time');
            $table->string('drone_type');
            $table->integer('height');
            $table->integer('overlap');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('aerial_photos');
    }
};
