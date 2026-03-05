<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TranslationRequest extends FormRequest
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
        $translationId = $this->route('id');
        $languageId = $this->input('language_id');
        $group = $this->input('group');

        // Build unique rule - handle null group properly
        // In SQL, NULL != NULL, so we need to handle null groups specially
        $uniqueRule = Rule::unique('translations', 'key')
            ->where('language_id', $languageId)
            ->ignore($translationId);

        // Add group condition - handle null properly using whereNull when group is empty
        if (empty($group)) {
            // For null/empty groups, use whereNull to properly match NULL values
            $uniqueRule->whereNull('group');
        } else {
            $uniqueRule->where('group', $group);
        }

        return [
            'language_id' => 'required|exists:languages,id',
            'key' => [
                'required',
                'string',
                'max:255',
                $uniqueRule,
            ],
            'value' => 'required|string',
            'group' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'language_id.required' => 'The language is required.',
            'language_id.exists' => 'The selected language does not exist.',
            'key.required' => 'The translation key is required.',
            'key.unique' => 'This translation key already exists for the selected language and group.',
            'value.required' => 'The translation value is required.',
            'group.max' => 'The group name may not be greater than 255 characters.',
        ];
    }
}

