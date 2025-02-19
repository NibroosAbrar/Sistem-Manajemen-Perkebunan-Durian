<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role_id', 'role'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            if ($user->isDirty('role_id')) {
                $user->role = optional($user->roleRelation)->name;
            }
        });
    }

    public function roleRelation(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

}









