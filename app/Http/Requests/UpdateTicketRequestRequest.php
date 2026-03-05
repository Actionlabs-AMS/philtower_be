<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullableKeys = ['user_id', 'parent_ticket_id', 'service_type_id', 'ticket_status_id', 'slas_id', 'assigned_to'];
        $merge = [];
        foreach ($nullableKeys as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $merge[$key] = null;
            }
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'parent_ticket_id' => ['nullable', 'integer'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'description' => ['nullable', 'string'],
            'attachment_metadata' => ['nullable'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:51200'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:100'],
            'ticket_status_id' => ['nullable', 'integer', 'exists:ticket_statuses,id'],
            'slas_id' => ['nullable', 'integer', 'exists:slas,id'],
            'for_approval' => ['nullable', 'integer', 'in:1,2,3'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'submitted_at' => ['nullable', 'date'],
            'resolved_at' => ['nullable', 'date'],
            'closed_at' => ['nullable', 'date'],
        ];
    }
}
