<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'two_factor' => 'sometimes|array',
            'two_factor.enabled' => 'sometimes|boolean',
            'two_factor.required' => 'sometimes|boolean',
            'two_factor.backup_codes_count' => 'sometimes|integer|min:5|max:20',
            'session' => 'sometimes|array',
            'session.session_enabled' => 'sometimes|boolean',
            'session.session_timeout' => 'sometimes|integer|min:5|max:1440',
            'session.max_login_attempts' => 'sometimes|integer|min:3|max:10',
            'session.lockout_duration' => 'sometimes|integer|min:5|max:60',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'two_factor.backup_codes_count.min' => 'Backup codes count must be at least 5.',
            'two_factor.backup_codes_count.max' => 'Backup codes count must not exceed 20.',
            'session.session_timeout.min' => 'Session timeout must be at least 5 minutes.',
            'session.session_timeout.max' => 'Session timeout must not exceed 1440 minutes.',
            'session.max_login_attempts.min' => 'Max login attempts must be at least 3.',
            'session.max_login_attempts.max' => 'Max login attempts must not exceed 10.',
            'session.lockout_duration.min' => 'Lockout duration must be at least 5 minutes.',
            'session.lockout_duration.max' => 'Lockout duration must not exceed 60 minutes.',
        ];
    }
}

