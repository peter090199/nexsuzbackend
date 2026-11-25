<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactInquiry extends Model
{
    public const STATUSES = ['new', 'read', 'replied', 'closed'];

    protected $table = 'contact_inquiries';
    protected $guarded = [];
    protected $casts = ['archived_at' => 'datetime', 'notified_at' => 'datetime', 'last_activity_at' => 'datetime'];

    public function messages(): HasMany
    {
        return $this->hasMany(ContactInquiryMessage::class, 'inquiry_id')->orderBy('created_at')->orderBy('id');
    }
}
