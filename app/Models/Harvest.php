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

    protected $casts = [
        'fruit_condition' => 'decimal:2',
        'total_weight' => 'decimal:2',
        'average_weight_per_fruit' => 'decimal:2'
    ];

    // Relasi dengan model Tree
    public function tree()
    {
        return $this->belongsTo(Tree::class);
    }
}
