<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NavigationRequest extends FormRequest
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
    $id = $this->route('id');
    
    return [
      "name" => "required|string|max:255|regex:/^[a-zA-Z0-9,&-_\s]+$/|unique:navigations,name," . ($id ?? 'NULL'),
      "slug" => "required|string|max:255|regex:/^[a-zA-Z0-9-_\s]+$/|unique:navigations,slug," . ($id ?? 'NULL'),
      "icon" => "nullable|string|max:255",
      "description" => "nullable|string|max:500",
      "parent_id" => "nullable|integer|exists:navigations,id",
      "active" => "required|boolean",
      "show_in_menu" => "required|boolean",
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'The navigation name is required.',
      'name.regex' => 'The navigation name can only contain letters, numbers, and special characters (, & - _).',
      'name.unique' => 'This navigation name is already taken.',
      'slug.required' => 'The slug is required.',
      'slug.regex' => 'The slug can only contain letters, numbers, hyphens, underscores, and spaces.',
      'slug.unique' => 'This slug is already taken.',
      'parent_id.exists' => 'The selected parent navigation does not exist.',
      'active.required' => 'The active status is required.',
      'active.boolean' => 'The active status must be true or false.',
      'show_in_menu.required' => 'The show in menu status is required.',
      'show_in_menu.boolean' => 'The show in menu status must be true or false.',
    ];
  }
}
