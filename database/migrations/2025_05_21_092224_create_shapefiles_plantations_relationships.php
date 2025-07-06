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
        // 1. Buat tabel shapefiles jika belum ada
        if (!Schema::hasTable('shapefiles')) {
            Schema::create('shapefiles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->enum('type', ['plantation', 'tree']);
                $table->string('file_path')->nullable();
                $table->text('geometry')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('user_id')->constrained();
                $table->timestamps();
                $table->boolean('processed')->default(false);
            });
        }
        
        // 2. Tambahkan kolom shapefile_id ke tabel plantations jika belum ada
        if (Schema::hasTable('plantations') && !Schema::hasColumn('plantations', 'shapefile_id')) {
            Schema::table('plantations', function (Blueprint $table) {
                $table->foreignId('shapefile_id')->nullable()->after('id')
                    ->constrained('shapefiles')->onDelete('set null')
                    ->comment('Relasi ke data shapefile');
            });
        }
        
        // 3. Hapus kolom user_id dari tabel plantations jika ada
        if (Schema::hasTable('plantations') && Schema::hasColumn('plantations', 'user_id')) {
            Schema::table('plantations', function (Blueprint $table) {
                // Periksa apakah constraint foreign key ada
                $foreignKeys = DB::select("
                    SELECT tc.constraint_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
                    WHERE tc.constraint_type = 'FOREIGN KEY' 
                    AND tc.table_name = 'plantations'
                    AND kcu.column_name = 'user_id'
                ");
                
                if (!empty($foreignKeys)) {
                    foreach ($foreignKeys as $foreignKey) {
                        $table->dropForeign($foreignKey->constraint_name);
                    }
                }
                
                $table->dropColumn('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Tambahkan kembali kolom user_id ke tabel plantations
        if (Schema::hasTable('plantations') && !Schema::hasColumn('plantations', 'user_id')) {
            Schema::table('plantations', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')
                    ->constrained('users')->onDelete('cascade');
            });
        }
        
        // 2. Hapus kolom shapefile_id dari tabel plantations
        if (Schema::hasTable('plantations') && Schema::hasColumn('plantations', 'shapefile_id')) {
            Schema::table('plantations', function (Blueprint $table) {
                // Periksa apakah constraint foreign key ada
                $foreignKeys = DB::select("
                    SELECT tc.constraint_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
                    WHERE tc.constraint_type = 'FOREIGN KEY' 
                    AND tc.table_name = 'plantations'
                    AND kcu.column_name = 'shapefile_id'
                ");
                
                if (!empty($foreignKeys)) {
                    foreach ($foreignKeys as $foreignKey) {
                        $table->dropForeign($foreignKey->constraint_name);
                    }
                }
                
                $table->dropColumn('shapefile_id');
            });
        }
        
        // 3. Hapus tabel shapefiles jika masih ada
        Schema::dropIfExists('shapefiles');
    }
};
