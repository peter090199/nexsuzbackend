<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Post extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'code',
        'posts_uuid',
        'transNo',
        'caption',
        'status',
        'created_by',
        'updated_by',
    ];

}
