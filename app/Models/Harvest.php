<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Harvest extends Model
{
    use HasFactory;

    protected $table = 'harvests';

    protected $fillable = [
        'tree_id',
        'tanggal_panen',
        'fruit_count',
        'total_weight',
        'average_weight_per_fruit',
        'fruit_condition',
        'unit'
    ];

    // Relasi dengan model Tree
    public function tree()
    {
        return $this->belongsTo(Tree::class);
    }
}
