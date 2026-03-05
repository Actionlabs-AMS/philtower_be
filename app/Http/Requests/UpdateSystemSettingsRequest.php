<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
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
            'security' => 'sometimes|array',
            'security.session_timeout' => 'sometimes|integer|min:5|max:480',
            'security.max_login_attempts' => 'sometimes|integer|min:3|max:10',
            'security.password_min_length' => 'sometimes|integer|min:6|max:32',
            'general' => 'sometimes|array',
            'general.site_name' => 'sometimes|string|max:255',
            'general.site_description' => 'sometimes|string|max:500',
            'general.timezone' => 'sometimes|string|max:50',
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
            'security.session_timeout.min' => 'Session timeout must be at least 5 minutes.',
            'security.session_timeout.max' => 'Session timeout must not exceed 480 minutes.',
            'security.max_login_attempts.min' => 'Max login attempts must be at least 3.',
            'security.max_login_attempts.max' => 'Max login attempts must not exceed 10.',
            'security.password_min_length.min' => 'Password minimum length must be at least 6 characters.',
            'security.password_min_length.max' => 'Password minimum length must not exceed 32 characters.',
            'general.site_name.max' => 'Site name may not be greater than 255 characters.',
            'general.site_description.max' => 'Site description may not be greater than 500 characters.',
            'general.timezone.max' => 'Timezone may not be greater than 50 characters.',
        ];
    }
}

