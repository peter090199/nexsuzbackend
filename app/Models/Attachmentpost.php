<?php

namespace App\Models;

use App\Helpers\MediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Attachmentpost extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'code',
        'transNo',
        'posts_uuid',
        'posts_uuind',
        'status',
        'path_url',
        'posts_type',
        'created_by',
        'updated_by',
    ];

    /** Always hand the client a URL that works on the current host (see MediaUrl). */
    public function getPathUrlAttribute($value)
    {
        return MediaUrl::normalize($value);
    }


}
