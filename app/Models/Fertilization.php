<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fertilization extends Model
{
    use HasFactory;

    protected $table = 'fertilization_records';

    protected $fillable = [
        'date',
        'fertilizer_type',
        'amount',
        'area',
        'officer'
    ];
}
