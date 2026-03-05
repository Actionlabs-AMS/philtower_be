<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'parent_ticket_id' => ['nullable', 'integer'],
            'service_type_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'description' => ['nullable', 'string'],
            'attachment_metadata' => ['nullable', 'array'],
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
