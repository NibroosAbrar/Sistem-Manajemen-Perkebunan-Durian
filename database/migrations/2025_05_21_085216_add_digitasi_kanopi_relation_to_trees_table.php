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
        // Periksa apakah tabel digitasi_kanopi ada sebelum menambahkan relasi
        $tableExists = DB::select("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = 'digitasi_kanopi'
            ) as exists
        ");

        if ($tableExists[0]->exists && Schema::hasTable('trees')) {
            Schema::table('trees', function (Blueprint $table) {
                // Tambahkan kolom digitasi_kanopi_id jika belum ada
                if (!Schema::hasColumn('trees', 'digitasi_kanopi_id')) {
                    $table->foreignId('digitasi_kanopi_id')->nullable()->after('id')
                        ->comment('Relasi ke data digitasi kanopi hasil import dari QGIS');
                        
                    // Tambahkan foreign key constraint jika tabel memiliki primary key id
                    // Cek dulu apakah kolom id ada di tabel digitasi_kanopi
                    $hasIdColumn = DB::select("
                        SELECT EXISTS (
                            SELECT FROM information_schema.columns 
                            WHERE table_schema = 'public' 
                            AND table_name = 'digitasi_kanopi' 
                            AND column_name = 'id'
                        ) as exists
                    ");
                    
                    if ($hasIdColumn[0]->exists) {
                        // Tambahkan foreign key constraint
                        $table->foreign('digitasi_kanopi_id')
                            ->references('id')
                            ->on('digitasi_kanopi')
                            ->onDelete('set null');
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('trees') && Schema::hasColumn('trees', 'digitasi_kanopi_id')) {
            Schema::table('trees', function (Blueprint $table) {
                // Cek apakah constraint foreign key ada
                $foreignKeys = DB::select("
                    SELECT tc.constraint_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
                    WHERE tc.constraint_type = 'FOREIGN KEY' 
                    AND tc.table_name = 'trees'
                    AND kcu.column_name = 'digitasi_kanopi_id'
                ");
                
                if (!empty($foreignKeys)) {
                    foreach ($foreignKeys as $foreignKey) {
                        $table->dropForeign($foreignKey->constraint_name);
                    }
                }
                
                $table->dropColumn('digitasi_kanopi_id');
            });
        }
    }
};
