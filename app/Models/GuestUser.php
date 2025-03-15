<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestUser extends Model
{
    use HasFactory;

    // Specify the table name if it does not follow Laravel's convention
    protected $table = 'guest_user';

    // Specify the primary key for the table
    protected $primaryKey = 'user_email';

    // Set the primary key type (string for email)
    protected $keyType = 'string';

    // Disable auto-incrementing, as email is not auto-incremented
    public $incrementing = false;

    // Allow mass assignment for specific fields
    protected $fillable = ['user_email', 'name', 'date_of_birth', 'gender', 'password', 'visit_count'];
}
