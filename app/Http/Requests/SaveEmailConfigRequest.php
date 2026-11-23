<?php

namespace App\Http\Requests;

use App\Services\MailConfigService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * SMTP settings. Used to save, update and (with a looser password rule) to test.
 * A blank password on update/test means "keep the one already stored".
 */
class SaveEmailConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the masteradmin route middleware is the gate
    }

    protected function prepareForValidation(): void
    {
        foreach (['host', 'username', 'from_address', 'from_name'] as $f) {
            if (is_string($this->input($f))) { $this->merge([$f => trim($this->input($f))]); }
        }
    }

    public function rules(): array
    {
        return self::fields($this->isMethod('post') && $this->routeIs('*store'));
    }

    /** Field rules, shared with the controller for unsaved-form overrides. */
    public static function fields(bool $passwordRequired): array
    {
        return [
            'provider'     => ['required', Rule::in(array_keys(MailConfigService::PRESETS))],
            'host'         => ['required', 'string', 'max:190', 'regex:/^[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?$/'],
            'port'         => ['required', 'integer', 'between:1,65535'],
            'encryption'   => ['required', Rule::in(['ssl', 'tls', 'none'])],
            'username'     => ['required', 'string', 'max:190'],
            'password'     => [$passwordRequired ? 'required' : 'nullable', 'string', 'max:255'],
            'from_address' => ['required', 'email:rfc', 'max:190'],
            'from_name'    => ['required', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return ['host.regex' => 'Enter a host name such as smtp.example.com (no spaces, no http://).'];
    }
}
