<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
      'name' => 'required|string|max:255|unique:roles,name,'.$this->id,
      'permissions' => 'array|required', // Expecting a permissions array
      'active' => 'boolean', // Whether the role is active
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'The role name is required.',
      'name.max' => 'The role name may not be greater than 255 characters.',
      'name.unique' => 'This role name is already taken.',
      'permissions.required' => 'Please select at least one permission.',
      'permissions.array' => 'The permissions must be provided as an array.'
    ];
  }
}
