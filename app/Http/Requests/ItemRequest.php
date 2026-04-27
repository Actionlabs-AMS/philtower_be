<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            "name" => "required|string|max:255",
            "code" => "required|string|max:50",
            "description" => "nullable|string|max:500",

            "active" => "nullable|boolean",
            "approval" => "nullable|boolean",

            // multi-select subcategories (stored as array or JSON)
            "subcategory_id" => "nullable|array",
            "subcategory_id.*" => "integer",

        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            "name.required" => "Name is required.",
            "code.required" => "Code is required.",

            "subcategory_id.*.integer" => "Each subcategory must be a valid ID.",
        ];
    }
}