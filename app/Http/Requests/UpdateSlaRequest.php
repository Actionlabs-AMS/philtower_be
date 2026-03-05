<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'severity' => ['nullable', 'string', 'max:20'],
            'response_minutes' => ['nullable', 'integer', 'min:0'],
            'resolution_minutes' => ['nullable', 'integer', 'min:0'],
            'pause_on_onhold' => ['boolean'],
            'pause_on_fe_visit' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('pause_on_onhold') && is_string($this->pause_on_onhold)) {
            $this->merge(['pause_on_onhold' => filter_var($this->pause_on_onhold, FILTER_VALIDATE_BOOLEAN)]);
        }
        if ($this->has('pause_on_fe_visit') && is_string($this->pause_on_fe_visit)) {
            $this->merge(['pause_on_fe_visit' => filter_var($this->pause_on_fe_visit, FILTER_VALIDATE_BOOLEAN)]);
        }
    }
}
