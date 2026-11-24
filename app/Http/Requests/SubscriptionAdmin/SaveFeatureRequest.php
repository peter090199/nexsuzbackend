<?php

namespace App\Http\Requests\SubscriptionAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create or update a catalog feature. The feature_code is only accepted on create (it is immutable). */
class SaveFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by the `subscriptionadmin` route middleware
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('feature_code')) {
            $this->merge(['feature_code' => strtoupper(trim((string) $this->input('feature_code')))]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'name'        => ['required', 'string', 'max:150'],
            'category'    => ['required', 'string', 'max:50'],
            'icon'        => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'value_type'  => ['required', Rule::in(['toggle', 'limit'])],
            'is_active'   => ['boolean'],
        ];
        if ($this->isMethod('post')) {
            $rules['feature_code'] = ['required', 'string', 'max:100', 'regex:/^[A-Z0-9_]+$/', 'unique:subscription_features,feature_code'];
        }
        return $rules;
    }

    public function messages(): array
    {
        return ['feature_code.regex' => 'Use capital letters, numbers and underscores only.'];
    }
}
