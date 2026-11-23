<?php

namespace App\Http\Requests\SubscriptionAdmin;

use Illuminate\Foundation\Http\FormRequest;

/** One request class for every subscriber action; only the fields of the called action are validated. */
class SubscriberActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'changePlan' => ['plan_id' => ['required', 'string', 'exists:userplanheader,planId'], 'remarks' => ['nullable', 'string', 'max:200']],
            'extend'     => ['days' => ['required', 'integer', 'min:1', 'max:730']],
            'suspend'    => ['reason' => ['nullable', 'string', 'max:200']],
            default      => [],
        };
    }
}
