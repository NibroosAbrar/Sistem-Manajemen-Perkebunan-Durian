<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Update tree_fertilization table
        Schema::table('tree_fertilization', function (Blueprint $table) {
            // Ubah kolom unit menjadi enum dengan pilihan g/tanaman
            $table->dropColumn('unit');
        });

        Schema::table('tree_fertilization', function (Blueprint $table) {
            $table->string('unit')->default('g/tanaman')->after('dosis_pupuk');
        });

        // Update data yang ada
        DB::table('tree_fertilization')->update([
            'unit' => 'g/tanaman'
        ]);

        // Update tree_pesticide table
        Schema::table('tree_pesticide', function (Blueprint $table) {
            // Ubah kolom unit menjadi enum dengan pilihan ml/tanaman
            $table->dropColumn('unit');
        });

        Schema::table('tree_pesticide', function (Blueprint $table) {
            $table->string('unit')->default('ml/tanaman')->after('dosis');
        });

        // Update data yang ada
        DB::table('tree_pesticide')->update([
            'unit' => 'ml/tanaman'
        ]);
    }

    public function down()
    {
        // Restore tree_fertilization table
        Schema::table('tree_fertilization', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('tree_fertilization', function (Blueprint $table) {
            $table->string('unit')->after('dosis_pupuk');
        });

        // Restore tree_pesticide table
        Schema::table('tree_pesticide', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('tree_pesticide', function (Blueprint $table) {
            $table->string('unit')->after('dosis');
        });
    }
};
