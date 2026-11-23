<?php

namespace App\Http\Resources\SubscriptionAdmin;

use App\Services\SubscriptionAdminService;
use Illuminate\Http\Resources\Json\JsonResource;

/** Wraps a row of SubscriptionAdminService::subscriberQuery(). */
class SubscriberResource extends JsonResource
{
    public function toArray($request): array
    {
        $r = $this->resource;
        $status = SubscriptionAdminService::statusOf($r->sub_id ? $r : null);
        return [
            'code'     => $r->code,
            'name'     => $r->fullname,
            'email'    => $r->email,
            'role'     => $r->role_code,
            'plan_id'  => $r->planId,
            'plan'     => $r->plan_name,
            'status'   => $status,
            'start'    => $r->start_date,
            'end'      => $r->end_date,
            'days_left' => $r->end_date && $status === 'active' ? (int) ceil((strtotime($r->end_date) - time()) / 86400) : null,
            'payment_method' => $r->payment_method,
        ];
    }
}
