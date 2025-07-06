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
        'digitasi_id',
        'varietas',
        'tahun_tanam',
        'health_status',
        'fase',
        'latitude',
        'longitude',
        'sumber_bibit',
        'canopy_geometry',
        'shapefile_id',
        'polygon_index',
    ];

    // Allow manually setting the ID
    public $incrementing = false;

    // Set string type for the ID
    protected $keyType = 'string';

    // Tambahkan unique index untuk kombinasi ID pohon dan Plantation ID
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Memastikan ID selalu dalam format kapital
            if ($model->isDirty('id') && !is_null($model->id)) {
                $model->id = strtoupper($model->id);
            }
        });
    }

    public function plantation()
    {
        return $this->belongsTo(Plantation::class);
    }

    // Relasi untuk digitasi
    public function digitasi()
    {
        return $this->belongsTo(Digitasi::class);
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

    // Relasi untuk ZPT (Zat Pengatur Tumbuh)
    public function zpts()
    {
        return $this->hasMany(TreeZpt::class);
    }

    // Relasi untuk riwayat pertumbuhan
    public function growths()
    {
        return $this->hasMany(TreeGrowth::class);
    }

    // Relasi ke shapefile
    public function shapefile()
    {
        return $this->belongsTo(Shapefile::class);
    }

    // Accessor untuk mengubah format varietas menjadi kapital di awal kata
    public function getVarietasAttribute($value)
    {
        return ucwords(strtolower($value));
    }

    // Mutator untuk memastikan varietas disimpan dengan format yang benar
    public function setVarietasAttribute($value)
    {
        $this->attributes['varietas'] = ucwords(strtolower($value));
    }
}
