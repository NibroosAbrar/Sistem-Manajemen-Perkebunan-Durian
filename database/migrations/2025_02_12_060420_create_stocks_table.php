<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama barang
            $table->enum('category', ['bibit_pohon', 'pupuk', 'pestisida_fungisida', 'alat_perlengkapan']); // Kategori stok
            $table->integer('quantity'); // Jumlah stok
            $table->string('unit'); // Satuan (Kg, Liter, dll.)
            $table->date('date_added'); // Tanggal masuk stok
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stocks');
    }
};
