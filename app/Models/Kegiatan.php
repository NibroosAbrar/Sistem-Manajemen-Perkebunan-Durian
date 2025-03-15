<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    use HasFactory;

    protected $table = 'kegiatan'; // Sesuaikan dengan nama tabel di database
    protected $fillable = ['tanggal', 'jenis_kegiatan', 'deskripsi', 'petugas'];
}
