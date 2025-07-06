<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreePesticide extends Model
{
    use HasFactory;

    protected $table = 'tree_pesticide';

    protected $fillable = [
        'tree_id',
        'tanggal_pestisida',
        'nama_pestisida',
        'jenis_pestisida',
        'bentuk_pestisida',
        'dosis',
        'unit'
    ];

    protected $attributes = [
        'unit' => 'ml/tanaman'
    ];

    // Relasi dengan model Tree
    public function tree()
    {
        return $this->belongsTo(Tree::class);
    }
}
