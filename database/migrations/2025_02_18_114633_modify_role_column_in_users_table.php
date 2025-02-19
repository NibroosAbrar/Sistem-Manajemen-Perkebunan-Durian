<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Ubah kolom role menjadi nullable agar bisa menerima foreign key
            $table->string('role')->nullable()->change();

            // Tambahkan foreign key untuk menghubungkan role ke roles.name
            $table->foreign('role')->references('name')->on('roles')->onUpdate('CASCADE')->onDelete('SET NULL');
        });

        // Sinkronisasi awal: isi role berdasarkan role_id
        DB::statement("UPDATE users SET role = (SELECT name FROM roles WHERE roles.id = users.role_id)");
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus foreign key hanya jika ada
            $table->dropForeign(['role']);
        });
    }
};
