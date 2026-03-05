<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file_name' => 'sometimes|string|max:255',
            'caption' => 'nullable|string|max:500',
            'short_descriptions' => 'nullable|string|max:1000',
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
            'file_name.string' => 'File name must be a string.',
            'file_name.max' => 'File name must not exceed 255 characters.',
            'caption.string' => 'Caption must be a string.',
            'caption.max' => 'Caption must not exceed 500 characters.',
            'short_descriptions.string' => 'Description must be a string.',
            'short_descriptions.max' => 'Description must not exceed 1000 characters.',
        ];
    }
}
