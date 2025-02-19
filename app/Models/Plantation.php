<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;

class Plantation extends Model {
    use HasFactory, SpatialTrait;

    protected $fillable = ['user_id', 'name', 'area_size', 'location', 'soil_type', 'climate_zone'];
    
    protected $spatialFields = ['location'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function trees() {
        return $this->hasMany(Tree::class);
    }
}
