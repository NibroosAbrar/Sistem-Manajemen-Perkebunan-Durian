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
            // Tambahkan kolom selesai dengan default false
            $table->boolean('selesai')->default(false)->after('petugas');

            // Tambahkan kolom user_id sebagai foreign key
            $table->foreignId('user_id')->nullable()->after('selesai')->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['selesai', 'user_id']);
        });
    }
};
