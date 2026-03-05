<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestoreBackupRequest extends FormRequest
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
            'confirm' => 'required|boolean|accepted',
            'create_backup' => 'nullable|boolean',
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
            'confirm.required' => 'You must confirm the restore operation.',
            'confirm.boolean' => 'The confirm field must be true or false.',
            'confirm.accepted' => 'You must accept the restore confirmation.',
            'create_backup.boolean' => 'The create backup field must be true or false.',
        ];
    }
}

