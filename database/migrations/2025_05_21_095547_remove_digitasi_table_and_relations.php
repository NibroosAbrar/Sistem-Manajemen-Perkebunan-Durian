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
        // 1. Hapus kolom digitasi_id dari tabel trees
        if (Schema::hasTable('trees') && Schema::hasColumn('trees', 'digitasi_id')) {
            Schema::table('trees', function (Blueprint $table) {
                // Hapus foreign key
                $foreignKeys = DB::select("
                    SELECT tc.constraint_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
                    WHERE tc.constraint_type = 'FOREIGN KEY' 
                    AND tc.table_name = 'trees'
                    AND kcu.column_name = 'digitasi_id'
                ");
                
                if (!empty($foreignKeys)) {
                    foreach ($foreignKeys as $foreignKey) {
                        $table->dropForeign($foreignKey->constraint_name);
                    }
                }
                
                // Hapus kolom
                $table->dropColumn('digitasi_id');
            });
        }
        
        // 2. Hapus tabel digitasi
        Schema::dropIfExists('digitasi');
        
        // 3. Pastikan relasi users->shapefiles sudah ada
        if (Schema::hasTable('shapefiles') && !Schema::hasColumn('shapefiles', 'user_id')) {
            Schema::table('shapefiles', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('description')
                    ->constrained('users')->onDelete('cascade')
                    ->comment('Relasi ke user pemilik');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Buat kembali tabel digitasi jika perlu rollback
        if (!Schema::hasTable('digitasi')) {
            Schema::create('digitasi', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('aerial_photo_id')->constrained('aerial_photos');
                $table->foreignId('plantation_id')->constrained('plantations');
                $table->string('class')->comment('Nama kelas YOLO yang terdeteksi');
                $table->float('confidence')->nullable()->comment('Tingkat keyakinan deteksi YOLO');
                $table->geometry('geom', 'POLYGON', 4326)->comment('Geometri polygon hasil deteksi YOLO');
                $table->boolean('is_processed')->default(true);
                $table->json('detection_meta')->nullable()->comment('Metadata tambahan hasil deteksi');
                $table->timestamps();
            });
        }
        
        // 2. Tambahkan kembali kolom digitasi_id ke tabel trees
        if (Schema::hasTable('trees') && !Schema::hasColumn('trees', 'digitasi_id')) {
            Schema::table('trees', function (Blueprint $table) {
                $table->foreignId('digitasi_id')->nullable()->after('id')
                    ->constrained('digitasi')->onDelete('set null')
                    ->comment('Relasi ke data digitasi YOLO');
            });
        }
    }
};
