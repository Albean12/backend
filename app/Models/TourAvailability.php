<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourAvailability extends Model
{
    use HasFactory;

    protected $table = 'tour_availabilities'; // Explicitly set the table name

    protected $fillable = ['date', 'time', 'status'];
}
