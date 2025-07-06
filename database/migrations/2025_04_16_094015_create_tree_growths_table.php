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
        Schema::create('tree_growths', function (Blueprint $table) {
            $table->id();
            $table->string('tree_id');
            $table->foreign('tree_id')->references('id')->on('trees')->onDelete('cascade');
            $table->date('tanggal');
            $table->string('fase')->nullable();
            $table->float('tinggi')->nullable()->comment('dalam cm');
            $table->float('diameter')->nullable()->comment('dalam cm');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tree_growths');
    }
};
