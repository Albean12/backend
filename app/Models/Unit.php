<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'capacity', 'price', 'unit_code', 'status']; // Added 'status' to fillable

    // Relationship to fetch a single tenant assigned to the unit
    public function tenant()
    {
        return $this->hasOne(User::class, 'unit_id', 'id');
    }

    // Relationship to fetch all users assigned to the unit
    public function users()
    {
        return $this->hasMany(User::class, 'unit_id');
    }

    // Relationship to fetch all applications linked to the unit by unit_code
    public function applications()
    {
        return $this->hasMany(Application::class, 'reservation_details', 'unit_code');
    }

    // Method to check if the unit is available
    public function isAvailable()
    {
        return $this->status === 'available' && $this->users()->count() < $this->capacity;
    }
}
