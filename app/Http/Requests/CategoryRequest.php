<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
      "name" => "required|regex:/^[a-zA-Z0-9,&-_\s]+$/|unique:categories,name,".$this->id,
      "slug" => "required|string|regex:/^[a-zA-Z0-9,&-_\s]+$/",
      "descriptions" => "nullable|regex:/^[a-zA-Z0-9,&-_\s]+$/",
      "parent_id" => [
        'nullable',
        'integer',
        'exists:categories,id',
        function ($attribute, $value, $fail) {
          if ($value && $this->id && $value == $this->id) {
            $fail('A category cannot be its own parent.');
          }
        }
      ],
    ];
  }

  public function messages(): array
	{
		return [
			"name.regex" => "The name field must only contain letters, numbers, and spaces.",
      "parent_id.exists" => "The selected parent category does not exist.",
      "parent_id.integer" => "The parent category must be a valid category.",
      "parent_id.*" => "A category cannot be its own parent.",
		];
	}
}
