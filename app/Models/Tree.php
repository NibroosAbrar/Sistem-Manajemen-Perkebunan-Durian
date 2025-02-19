<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

class Tree extends Model {
    use HasFactory, SpatialTrait;

    protected $fillable = ['plantation_id', 'species', 'age', 'health_status', 'productivity', 'location', 'canopy_geometry'];

    protected $spatialFields = ['location', 'canopy_geometry'];

    public function plantation() {
        return $this->belongsTo(Plantation::class);
    }
}
