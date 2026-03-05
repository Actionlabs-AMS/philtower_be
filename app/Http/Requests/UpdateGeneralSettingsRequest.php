<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingsRequest extends FormRequest
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
        $rules = [
            'site' => 'sometimes|array',
            'site.site_name' => 'sometimes|string|max:255',
            'site.site_description' => 'sometimes|string|max:500',
            'site.auth_logo' => 'sometimes|nullable', // Allow null for deletion
            'site.sidenav_logo' => 'sometimes|nullable', // Allow null for deletion
            'site.remove_auth_logo' => 'sometimes|string', // Remove flag for FormData
            'site.remove_sidenav_logo' => 'sometimes|string', // Remove flag for FormData
            'date_time' => 'sometimes|array',
            'date_time.timezone' => 'sometimes|string|max:50',
            'date_time.date_format' => 'sometimes|string|max:50',
            'date_time.time_format' => 'sometimes|string|max:50',
            'language' => 'sometimes|array',
            'language.default_language' => 'sometimes|string|max:10',
        ];

        // Only add file validation if files are actually being uploaded
        if ($this->hasFile('site.auth_logo')) {
            $rules['site.auth_logo'] = 'image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        }
        if ($this->hasFile('site.sidenav_logo')) {
            $rules['site.sidenav_logo'] = 'image|mimes:jpeg,png,jpg,gif,svg|max:2048';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'site.site_name.max' => 'Site name may not be greater than 255 characters.',
            'site.site_description.max' => 'Site description may not be greater than 500 characters.',
            'date_time.timezone.max' => 'Timezone may not be greater than 50 characters.',
            'date_time.date_format.max' => 'Date format may not be greater than 50 characters.',
            'date_time.time_format.max' => 'Time format may not be greater than 50 characters.',
            'language.default_language.max' => 'Default language may not be greater than 10 characters.',
            'site.auth_logo.image' => 'Auth logo must be an image.',
            'site.auth_logo.mimes' => 'Auth logo must be a file of type: jpeg, png, jpg, gif, svg.',
            'site.auth_logo.max' => 'Auth logo may not be greater than 2048 kilobytes.',
            'site.sidenav_logo.image' => 'Sidenav logo must be an image.',
            'site.sidenav_logo.mimes' => 'Sidenav logo must be a file of type: jpeg, png, jpg, gif, svg.',
            'site.sidenav_logo.max' => 'Sidenav logo may not be greater than 2048 kilobytes.',
        ];
    }
}

