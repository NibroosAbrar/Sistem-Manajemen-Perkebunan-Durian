<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreeDetection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'shapefile_id',
        'plantation_id',
        'user_id',
        'tree_count',
        'geometry',
        'description'
    ];

    /**
     * Cast attributes to appropriate types
     */
    protected $casts = [
        'tree_count' => 'integer'
    ];

    /**
     * Get the plantation associated with the tree detection.
     */
    public function plantation(): BelongsTo
    {
        return $this->belongsTo(Plantation::class);
    }

    /**
     * Get the shapefile associated with the tree detection.
     */
    public function shapefile(): BelongsTo
    {
        return $this->belongsTo(Shapefile::class);
    }

    /**
     * Get the user associated with the tree detection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke model Tree
     */
    public function trees()
    {
        return $this->hasMany(Tree::class);
    }
} 