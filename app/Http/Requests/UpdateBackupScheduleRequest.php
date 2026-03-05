<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBackupScheduleRequest extends FormRequest
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
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|in:database,files,full',
            'frequency' => 'nullable|in:daily,weekly,monthly,custom',
            'time' => 'nullable|date_format:H:i',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'cron_expression' => 'nullable|string',
            'retention_days' => 'nullable|integer|min:1',
            'compression' => 'nullable|in:none,gzip,zip',
            'encrypted' => 'nullable|boolean',
            'storage_disk' => 'nullable|in:local,s3',
            'tables_included' => 'nullable|array',
            'files_included' => 'nullable|array',
            'active' => 'nullable|boolean',
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
            'name.max' => 'The schedule name may not be greater than 255 characters.',
            'type.in' => 'The backup type must be one of: database, files, or full.',
            'frequency.in' => 'The frequency must be one of: daily, weekly, monthly, or custom.',
            'time.date_format' => 'The time must be in the format HH:mm (e.g., 02:00).',
            'day_of_week.integer' => 'The day of week must be an integer.',
            'day_of_week.min' => 'The day of week must be between 0 (Sunday) and 6 (Saturday).',
            'day_of_week.max' => 'The day of week must be between 0 (Sunday) and 6 (Saturday).',
            'day_of_month.integer' => 'The day of month must be an integer.',
            'day_of_month.min' => 'The day of month must be between 1 and 31.',
            'day_of_month.max' => 'The day of month must be between 1 and 31.',
            'cron_expression.string' => 'The cron expression must be a string.',
            'retention_days.integer' => 'The retention days must be an integer.',
            'retention_days.min' => 'The retention days must be at least 1.',
            'compression.in' => 'The compression type must be one of: none, gzip, or zip.',
            'encrypted.boolean' => 'The encrypted field must be true or false.',
            'storage_disk.in' => 'The storage disk must be either local or s3.',
            'tables_included.array' => 'The tables included must be an array.',
            'files_included.array' => 'The files included must be an array.',
            'active.boolean' => 'The active field must be true or false.',
        ];
    }
}

