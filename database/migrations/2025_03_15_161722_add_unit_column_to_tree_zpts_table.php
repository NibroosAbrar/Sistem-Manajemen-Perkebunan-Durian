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
        Schema::table('tree_zpts', function (Blueprint $table) {
            $table->string('unit')->nullable()->after('volume_larutan')->default('ml');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tree_zpts', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
