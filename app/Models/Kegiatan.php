<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    use HasFactory;

    protected $table = 'kegiatan'; // Sesuaikan dengan nama tabel di database
    protected $fillable = [
        'nama_kegiatan',
        'jenis_kegiatan',
        'deskripsi_kegiatan',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'user_id'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    /**
     * Mendapatkan user yang memiliki kegiatan ini
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
