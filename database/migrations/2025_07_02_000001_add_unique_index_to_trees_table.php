<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        // Pastikan semua ID pohon dalam format kapital
        DB::statement('UPDATE trees SET id = UPPER(id)');

        // Tambahkan unique index ke kombinasi id pohon dan plantation_id
        Schema::table('trees', function (Blueprint $table) {
            // Periksa apakah index sudah ada sebelum menambahkannya
            if (!Schema::hasIndex('trees', 'trees_id_plantation_id_unique')) {
                $table->unique(['id', 'plantation_id'], 'trees_id_plantation_id_unique');
            }
        });
    }

    public function down() {
        Schema::table('trees', function (Blueprint $table) {
            // Periksa apakah index ada sebelum menghapusnya
            if (Schema::hasIndex('trees', 'trees_id_plantation_id_unique')) {
                $table->dropIndex('trees_id_plantation_id_unique');
            }
        });
    }
}; 