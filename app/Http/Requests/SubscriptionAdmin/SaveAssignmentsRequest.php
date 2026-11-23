<?php

namespace App\Http\Requests\SubscriptionAdmin;

use Illuminate\Foundation\Http\FormRequest;

class SaveAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                => ['required', 'array', 'min:1'],
            'items.*.feature_id'   => ['required', 'integer', 'exists:subscription_features,id'],
            'items.*.enabled'      => ['required', 'boolean'],
            'items.*.limit'        => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'items.*.unlimited'    => ['boolean'],
        ];
    }
}
