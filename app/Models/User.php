<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'username', 'email', 'password', 'role_id', 'role'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            if ($user->isDirty('role_id')) {
                $user->role = optional($user->roleRelation)->name;
            }
        });
    }

    /**
     * Mengganti notifikasi reset password dengan notifikasi kustom.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Relasi ke banyak kegiatan
    public function kegiatan()
    {
        return $this->hasMany(Kegiatan::class);
    }

    /**
     * Relasi one-to-many dengan shapefiles
     * User dapat memiliki banyak shapefile
     */
    public function shapefiles(): HasMany
    {
        return $this->hasMany(Shapefile::class);
    }

    /**
     * Relasi yang menunjukkan semua plantation yang dimiliki user melalui shapefile
     */
    public function plantations()
    {
        return $this->hasManyThrough(Plantation::class, Shapefile::class);
    }
}









