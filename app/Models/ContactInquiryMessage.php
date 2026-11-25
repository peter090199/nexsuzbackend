<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactInquiryMessage extends Model
{
    protected $table = 'contact_inquiry_messages';
    protected $guarded = [];
    protected $casts = ['attachments' => 'array', 'is_html' => 'boolean'];
}
