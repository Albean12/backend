<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'status',
        'file_path',
        'schedule',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
