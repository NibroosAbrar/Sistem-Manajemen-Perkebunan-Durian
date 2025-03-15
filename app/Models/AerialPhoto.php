<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AerialPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'resolution',
        'capture_time',
        'drone_type',
        'height',
        'overlap'
    ];

    protected $casts = [
        'capture_time' => 'datetime',
        'resolution' => 'float',
        'height' => 'integer',
        'overlap' => 'integer'
    ];
}
