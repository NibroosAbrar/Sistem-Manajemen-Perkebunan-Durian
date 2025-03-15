<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plantation extends Model {
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'geometry',
        'latitude',
        'longitude',
        'luas_area',
        'tipe_tanah'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'luas_area' => 'decimal:2'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function trees() {
        return $this->hasMany(Tree::class);
    }
}
