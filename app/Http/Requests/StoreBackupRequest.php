<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBackupRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'type' => 'required|in:database,files,full',
            'compression' => 'nullable|in:none,gzip,zip',
            'encrypted' => 'nullable|boolean',
            'storage_disk' => 'nullable|in:local,s3',
            'tables_included' => 'nullable|array',
            'files_included' => 'nullable|array',
            'retention_days' => 'nullable|integer|min:1',
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
            'name.required' => 'The backup name is required.',
            'name.max' => 'The backup name may not be greater than 255 characters.',
            'type.required' => 'The backup type is required.',
            'type.in' => 'The backup type must be one of: database, files, or full.',
            'compression.in' => 'The compression type must be one of: none, gzip, or zip.',
            'encrypted.boolean' => 'The encrypted field must be true or false.',
            'storage_disk.in' => 'The storage disk must be either local or s3.',
            'tables_included.array' => 'The tables included must be an array.',
            'files_included.array' => 'The files included must be an array.',
            'retention_days.integer' => 'The retention days must be an integer.',
            'retention_days.min' => 'The retention days must be at least 1.',
        ];
    }
}

