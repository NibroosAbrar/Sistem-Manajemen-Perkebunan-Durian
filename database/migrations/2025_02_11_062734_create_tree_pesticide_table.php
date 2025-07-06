<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tree_pesticide', function (Blueprint $table) {
            $table->id();
            $table->string('tree_id', 20);
            $table->foreign('tree_id')->references('id')->on('trees')->onDelete('cascade');
            $table->date('tanggal_pestisida');
            $table->string('nama_pestisida')->nullable();
            $table->string('jenis_pestisida')->nullable();
            $table->decimal('dosis', 8, 2)->nullable();
            $table->enum('unit', ['ml/pohon', 'g/pohon']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tree_pesticide');
    }
};
