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
        Schema::table('kegiatan', function (Blueprint $table) {
            // Tambah kolom baru
            $table->string('nama_kegiatan')->after('id')->nullable(); // Sesuaikan posisi jika perlu
            $table->date('tanggal_mulai')->after('deskripsi')->nullable(); // Asumsi kolom deskripsi sudah ada
            $table->string('status')->default('Belum Berjalan')->after('tanggal')->nullable(); // Asumsi kolom tanggal sudah ada

            // Ubah nama kolom jika sudah ada
            if (Schema::hasColumn('kegiatan', 'deskripsi')) {
                $table->renameColumn('deskripsi', 'deskripsi_kegiatan');
            }
            if (Schema::hasColumn('kegiatan', 'tanggal')) {
                $table->renameColumn('tanggal', 'tanggal_selesai');
            }

            // Hapus kolom 'selesai' jika ada, setelah data dimigrasikan (jika perlu)
            // Untuk saat ini, kita bisa biarkan atau drop jika tidak ada data penting yang perlu ditransfer
            // Jika Anda ingin mentransfer data dari 'selesai' ke 'status', itu perlu dilakukan sebelum drop
            if (Schema::hasColumn('kegiatan', 'selesai')) {
                // Contoh migrasi data sederhana (sesuaikan jika perlu)
                // DB::table('kegiatan')->where('selesai', true)->update(['status' => 'Selesai']);
                // DB::table('kegiatan')->where('selesai', false)->whereNull('status')->update(['status' => 'Belum Berjalan']); // Hanya jika status belum diisi
                $table->dropColumn('selesai');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            // Kembalikan nama kolom
            if (Schema::hasColumn('kegiatan', 'deskripsi_kegiatan')) {
                $table->renameColumn('deskripsi_kegiatan', 'deskripsi');
            }
            if (Schema::hasColumn('kegiatan', 'tanggal_selesai')) {
                $table->renameColumn('tanggal_selesai', 'tanggal');
            }

            // Hapus kolom yang ditambahkan di 'up'
            $table->dropColumn(['nama_kegiatan', 'tanggal_mulai', 'status']);

            // Tambahkan kembali kolom 'selesai' jika memang ada sebelumnya dan perlu di-rollback
            // Ini mungkin memerlukan logika tambahan jika tipe data atau default value berbeda
            if (!Schema::hasColumn('kegiatan', 'selesai')) {
                 $table->boolean('selesai')->default(false)->after('deskripsi'); // Sesuaikan posisi dan default
            }
        });
    }
};
