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
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tree_id')->constrained('trees')->onDelete('cascade');
            $table->date('tanggal_panen');
            $table->float('total_weight')->nullable();
            $table->integer('fruit_count')->nullable();
            $table->float('average_weight_per_fruit')->nullable();
            $table->string('fruit_condition')->nullable();
            $table->enum('unit', ['kg', 'g']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
