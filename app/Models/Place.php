<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    // Specify the table name if it's not the plural form of the model
    protected $table = 'places';

    // Specify the fields that can be mass-assigned
    protected $fillable = [
        'name',
        'category',
        'latitude',
        'longitude',
        'description',
    ];
}
