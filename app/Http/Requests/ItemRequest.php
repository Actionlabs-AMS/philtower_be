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
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique(ServiceType::class, 'code')],
            'description' => ['nullable', 'string', 'max:500'],
            'active' => ['boolean'],
            'approval' => ['boolean'],
            // ✅ FIXED
            'subcategory_id' => ['nullable', 'array'],
            'subcategory_id.*' => ['integer', Rule::exists('subcategories', 'id')],
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
        // ✅ Fix empty or "null" subcategory_id
    if ($this->filled('subcategory_id') && ($this->subcategory_id === '' || $this->subcategory_id === 'null')) {
        $this->merge(['subcategory_id' => null]);
    }

    // Optional: ensure it's always an array
    if ($this->has('subcategory_id') && is_string($this->subcategory_id)) {
        $this->merge([
            'subcategory_id' => json_decode($this->subcategory_id, true),
        ]);
    }
    }
}