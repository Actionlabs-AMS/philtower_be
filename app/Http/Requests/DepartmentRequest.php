<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    /**
     * Authorize request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                "regex:/^[a-zA-Z0-9,&-_\s]+$/",
                Rule::unique('departments')
                    ->where(function ($query) {
                        return $query
                        ->where('name', $this->name)
                        ->where('code', $this->code);
                    })
                    ->ignore($this->id),
            ],

            'code' => [
                'required',
                'string',
                'max:100',
                "regex:/^[a-zA-Z0-9,&-_\s]+$/",
                Rule::unique('departments')
                    ->where(function ($query) {
                        return $query
                        ->where('name', $this->name)
                        ->where('code', $this->code);
                    })
                    ->ignore($this->id),
            ],

            'descriptions' => [
                'nullable',
                'string',
            ],

            'active' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Normalize input before validation
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => filter_var($this->active, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function messages(): array
	{
		return [
			"name.regex" => "The name field must only contain letters, numbers, and spaces.",
            "name.exists" => "The selected name already exists.",
            "code.regex" => "The code field must only contain letters, numbers, and spaces.",
            "code.exists" => "The selected code already exists.",
		];
	}
}