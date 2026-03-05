<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TagRequest extends FormRequest
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
      "name" => "required|regex:/^[a-zA-Z0-9,&-_\s]+$/|unique:tags,name,".$this->id,
      "slug" => "string|regex:/^[a-zA-Z0-9,&-_\s]+$/",
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'The tag name is required.',
      'name.regex' => 'The tag name can only contain letters, numbers, and special characters (, & - _).',
      'name.unique' => 'This tag name is already taken.',
      'slug.regex' => 'The slug can only contain letters, numbers, and special characters (, & - _).'
    ];
  }
}
