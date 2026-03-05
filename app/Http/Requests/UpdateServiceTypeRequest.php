<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Support\ServiceType;

class UpdateServiceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique(ServiceType::class, 'code')->ignore($id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'active' => ['boolean'],
            'approval' => ['boolean'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(ServiceType::class, 'id'),
                function ($attribute, $value, $fail) use ($id) {
                    if ($value && (int) $value === (int) $id) {
                        $fail('A service type cannot be its own parent.');
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'code' => 'code',
            'description' => 'description',
            'active' => 'active',
            'parent_id' => 'parent',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('active') && is_string($this->active)) {
            $this->merge(['active' => filter_var($this->active, FILTER_VALIDATE_BOOLEAN)]);
        }
        if ($this->has('approval') && is_string($this->approval)) {
            $this->merge(['approval' => filter_var($this->approval, FILTER_VALIDATE_BOOLEAN)]);
        }
        if ($this->filled('parent_id') && ($this->parent_id === '' || $this->parent_id === 'null')) {
            $this->merge(['parent_id' => null]);
        }
    }
}
