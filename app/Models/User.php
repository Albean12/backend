<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Relationships
     */
    public function applications()
    {
        return $this->hasMany(Application::class, 'email', 'email');
    }
    public function application()
    {
        return $this->hasOne(Application::class, 'email', 'email');
    }
    
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id');
    }

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'unit_id',
        'role',
    ];

    /**
     * Hidden attributes
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
