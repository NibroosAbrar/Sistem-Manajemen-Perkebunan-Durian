<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shapefile extends Model
{
    use HasFactory;

    /**
     * Nonaktifkan incrementing ID untuk mendukung ID kustom
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'id',
        'name',
        'type',
        'file_path',
        'geometry',
        'description',
        'user_id',
        'processed',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'processed' => 'boolean',
    ];

    /**
     * Get the user that owns this shapefile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plantations that belong to this shapefile.
     */
    public function plantations(): HasMany
    {
        return $this->hasMany(Plantation::class);
    }

    /**
     * Get the trees associated with this shapefile.
     * Only applicable when type is 'tree'
     */
    public function trees()
    {
        return $this->hasMany(Tree::class);
    }
} 