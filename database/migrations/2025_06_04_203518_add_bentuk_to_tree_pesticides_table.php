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
        Schema::table('tree_pesticide', function (Blueprint $table) {
            $table->string('bentuk_pestisida')->nullable()->after('jenis_pestisida');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tree_pesticide', function (Blueprint $table) {
            $table->dropColumn('bentuk_pestisida');
        });
    }
};
