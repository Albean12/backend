<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = ['user_email', 'emoji_rating', 'comment'];

    public function user()
    {
        return $this->belongsTo(GuestUser::class, 'user_email', 'user_email');
    }
}
