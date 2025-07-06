<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AerialPhoto extends Model
{
    use HasFactory;

    // Nonaktifkan auto-increment untuk ID
    public $incrementing = false;

    protected $fillable = [
        'id',
        'path',
        'bounds',
        'resolution',
        'capture_time',
        'drone_type',
        'height',
        'overlap',
        'user_id'
    ];

    protected $casts = [
        'capture_time' => 'datetime',
        'resolution' => 'float',
        'height' => 'integer',
        'overlap' => 'integer'
    ];

    /**
     * Mendapatkan user yang memiliki aerial photo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
