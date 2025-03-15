<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pesticide extends Model
{
    use HasFactory;

    protected $table = 'pesticide_records';

    protected $fillable = [
        'date',
        'pesticide_type',
        'dosage',
        'area',
        'target_pest',
        'officer'
    ];
}
