<?php

namespace App\Models\AccountPlan;

use Illuminate\Database\Eloquent\Model;

/** Catalog entry for a plan feature. feature_code is the immutable key shared with userplandetails. */
class SubscriptionFeature extends Model
{
    protected $table = 'subscription_features';

    protected $fillable = ['feature_code', 'name', 'category', 'icon', 'description', 'value_type', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** The key can be set when creating, never changed afterwards. */
    protected static function booted()
    {
        static::updating(function (self $feature) {
            if ($feature->isDirty('feature_code')) {
                $feature->feature_code = $feature->getOriginal('feature_code');
            }
        });
        // Catalog changes (e.g. disabling a feature) alter what plans offer, so recompute everyone's features.
        static::saved(fn () => \App\Services\PlanService::bust());
        static::deleted(fn () => \App\Services\PlanService::bust());
    }
}
