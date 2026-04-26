<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'sometimes|string|max:255|unique:ticket_priorities,label,' . $this->ticketPriority->id,
            'level' => 'sometimes|numeric|min:0|max:100',
        ];
    }
}
