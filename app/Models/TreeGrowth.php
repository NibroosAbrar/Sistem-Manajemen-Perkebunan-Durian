<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreeGrowth extends Model
{
    use HasFactory;

    protected $fillable = [
        'tree_id',
        'tanggal',
        'fase',
        'tinggi',
        'diameter'
    ];

    // Relasi dengan model Tree
    public function tree()
    {
        return $this->belongsTo(Tree::class);
    }
}
