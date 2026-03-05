<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Support\TicketStatus;

class StoreTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique(TicketStatus::class, 'code')],
            'label' => ['nullable', 'string', 'max:100'],
            'is_closed' => ['boolean'],
            'is_on_hold' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['is_closed', 'is_on_hold'] as $key) {
            if ($this->has($key) && is_string($this->$key)) {
                $this->merge([$key => filter_var($this->$key, FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }
}
