<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreeZpt extends Model
{
    use HasFactory;

    protected $fillable = [
        'tree_id',
        'tanggal_aplikasi',
        'nama_zpt',
        'merek',
        'jenis_senyawa',
        'konsentrasi',
        'volume_larutan',
        'fase_pertumbuhan',
        'metode_aplikasi',
        'unit'
    ];

    protected $casts = [
        'tanggal_aplikasi' => 'date',
        'volume_larutan' => 'decimal:2'
    ];

    /**
     * Get the tree that owns the ZPT record.
     */
    public function tree()
    {
        return $this->belongsTo(Tree::class);
    }
} 