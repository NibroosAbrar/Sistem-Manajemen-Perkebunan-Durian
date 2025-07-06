<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreeFertilization extends Model
{
    use HasFactory;

    protected $table = 'tree_fertilization';

    protected $fillable = [
        'tree_id',
        'tanggal_pemupukan',
        'nama_pupuk',
        'jenis_pupuk',
        'bentuk_pupuk',
        'dosis_pupuk',
        'unit'
    ];

    protected $attributes = [
        'unit' => 'g/tanaman'
    ];

    // Relasi dengan model Tree
    public function tree()
    {
        return $this->belongsTo(Tree::class);
    }
}
