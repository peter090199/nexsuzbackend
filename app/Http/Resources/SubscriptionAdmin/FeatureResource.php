<?php

namespace App\Http\Resources\SubscriptionAdmin;

use Illuminate\Http\Resources\Json\JsonResource;

class FeatureResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'key'         => $this->feature_code,
            'name'        => $this->name,
            'category'    => $this->category,
            'icon'        => $this->icon,
            'description' => $this->description,
            'value_type'  => $this->value_type,
            'active'      => (bool) $this->is_active,
            'plans_count' => $this->plans_count ?? null,
        ];
    }
}
