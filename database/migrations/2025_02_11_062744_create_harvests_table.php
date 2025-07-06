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
            $table->string('tree_id', 20);
            $table->foreign('tree_id')->references('id')->on('trees')->onDelete('cascade');
            $table->date('tanggal_panen');
            $table->float('total_weight')->nullable();
            $table->integer('fruit_count')->nullable();
            $table->float('average_weight_per_fruit')->nullable();
            $table->decimal('fruit_condition', 5, 2)->nullable()->comment('Persentase kondisi buah (0-100%)');
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
