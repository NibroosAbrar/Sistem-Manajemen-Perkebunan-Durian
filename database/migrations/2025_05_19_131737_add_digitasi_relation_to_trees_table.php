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
        // Pastikan tabel digitasi sudah ada sebelum menambahkan relasi
        if (Schema::hasTable('digitasi') && Schema::hasTable('trees')) {
            Schema::table('trees', function (Blueprint $table) {
                // Tambahkan kolom digitasi_id jika belum ada
                if (!Schema::hasColumn('trees', 'digitasi_id')) {
                    $table->foreignId('digitasi_id')->nullable()->after('id')
                        ->constrained('digitasi')->onDelete('set null')
                        ->comment('Relasi ke data digitasi YOLO');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hanya hapus kolom jika tabel trees ada
        if (Schema::hasTable('trees') && Schema::hasColumn('trees', 'digitasi_id')) {
            Schema::table('trees', function (Blueprint $table) {
                // Hapus foreign key dan kolom digitasi_id
                $table->dropForeign(['digitasi_id']);
                $table->dropColumn('digitasi_id');
            });
        }
    }
};
