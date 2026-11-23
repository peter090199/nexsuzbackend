<?php

namespace App\Models\AccountPlan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubscriptions extends Model
{
    use HasFactory;

    protected $table = 'usersubscription';

    protected $fillable = [
        'transNo',
        'code',
        'planId',
        'plan_name',    
        'amount',
        'payment_method',
        'transaction_id',
        'start_date',
        'end_date',
        'is_active',
        'remarks',      // e.g. "Assigned by <admin code>" on an admin plan change
    ];

    public function plan()
    {
        return $this->belongsTo(UserPlan::class, 'planId', 'planId');
    }

    /** plan, feature and subscription changes take effect straight away (everyone's features are recalculated) */
    protected static function booted()
    {
        $bust = fn () => \App\Services\PlanService::bust();
        static::saved($bust);
        static::deleted($bust);
    }
}
