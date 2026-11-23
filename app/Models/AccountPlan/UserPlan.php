<?php

namespace App\Models\AccountPlan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPlan extends Model
{
    use HasFactory;

    protected $table = 'userplanheader';

    // Primary Key
    protected $primaryKey = 'planId';

    // Set these based on your column type
    public $incrementing = false;   // true if planId is AUTO_INCREMENT
    protected $keyType = 'string';  // change to 'int' if planId is integer

    protected $fillable = [
        'planId',
        'button_name',
        'sort_number',
        'plan_name',
        'price',
        'tagmonthYear',
        'tag',
        'button_color',
        'description',
        'recordStatus',
        'plan_level',
        'role_code'
    ];

    public const AUDIENCES = ['DEF-USERS', 'DEF-CLIENT'];

    protected $appends = ['audience'];

    /** Who the plan is sold to. Stored in role_code; blank or legacy values mean job seekers. */
    public function getAudienceAttribute(): string
    {
        $v = trim((string) ($this->attributes['role_code'] ?? ''));
        return in_array($v, self::AUDIENCES, true) ? $v : 'DEF-USERS';
    }

    public function features()
    {
        return $this->hasMany(
            UserPlanDetails::class,
            'planId',
            'planId'
        );
    }

    /** plan, feature and subscription changes take effect straight away (everyone's features are recalculated) */
    protected static function booted()
    {
        $bust = fn () => \App\Services\PlanService::bust();
        static::saved($bust);
        static::deleted($bust);
    }
}
