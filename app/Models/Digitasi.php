<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Digitasi extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan
     *
     * @var string
     */
    protected $table = 'digitasi';

    /**
     * Atribut yang dapat diisi (mass assignable)
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'aerial_photo_id',
        'plantation_id',
        'class',
        'confidence',
        'geom',
        'is_processed',
        'detection_meta',
    ];

    /**
     * Atribut yang harus dikonversi
     *
     * @var array
     */
    protected $casts = [
        'confidence' => 'float',
        'is_processed' => 'boolean',
        'detection_meta' => 'array',
    ];

    /**
     * Relationship ke AerialPhoto
     */
    public function aerialPhoto(): BelongsTo
    {
        return $this->belongsTo(AerialPhoto::class);
    }

    /**
     * Relationship ke Plantation
     */
    public function plantation(): BelongsTo
    {
        return $this->belongsTo(Plantation::class);
    }

    /**
     * Relationship ke Trees
     */
    public function trees(): HasMany
    {
        return $this->hasMany(Tree::class);
    }
}
