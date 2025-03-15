<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tree extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'plantation_id',
        'varietas',
        'tahun_tanam',
        'health_status',
        'latitude',
        'longitude',
        'sumber_bibit',
        'canopy_geometry',
    ];

    // Allow manually setting the ID
    public $incrementing = false;

    public function plantation()
    {
        return $this->belongsTo(Plantation::class);
    }

    // Relasi untuk pemupukan
    public function fertilizations()
    {
        return $this->hasMany(TreeFertilization::class);
    }

    // Relasi untuk pestisida
    public function pesticides()
    {
        return $this->hasMany(TreePesticide::class);
    }

    // Relasi untuk panen
    public function harvests()
    {
        return $this->hasMany(Harvest::class);
    }
    
    // Relasi untuk riwayat kesehatan
    public function healthProfiles()
    {
        return $this->hasMany(TreeHealthProfile::class);
    }
}
