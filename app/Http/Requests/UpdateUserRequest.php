<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
			"user_login" => "required|string|unique:users,user_login,".$this->id,
			"user_email" => "required|email|unique:users,user_email,".$this->id,
			'user_role' => [
				'sometimes',
				function ($attribute, $value, $fail) {
					// Debug: Log what we're receiving
					\Log::info('[UpdateUserRequest] user_role validation:', [
						'type' => gettype($value),
						'value' => $value,
						'is_string' => is_string($value),
						'is_array' => is_array($value),
					]);
					
					$decoded = null;
					
					// Handle both string (JSON) and array (already decoded) cases
					if (is_string($value)) {
						// Try to decode JSON string
						$decoded = json_decode($value, true);
						
						// Check if it's a valid JSON string
						if (json_last_error() !== JSON_ERROR_NONE) {
							\Log::error('[UpdateUserRequest] JSON decode error:', [
								'error' => json_last_error_msg(),
								'value' => $value,
							]);
							return $fail("The $attribute must be a valid JSON string.");
						}
					} elseif (is_array($value)) {
						// Already decoded (Laravel might auto-decode nested JSON in some cases)
						$decoded = $value;
					} else {
						return $fail("The $attribute must be a valid JSON string or object.");
					}

					// Check if result is not an array/object
					if (!is_array($decoded)) {
						return $fail("The $attribute must be a valid JSON object.");
					}

					// If it's an empty array, reject
					if (empty($decoded)) {
						return $fail("The $attribute cannot be an empty array.");
					}

					// Must contain 'id'
					if (!isset($decoded['id'])) {
						return $fail("The $attribute must contain an 'id'.");
					}

					// Optional: reject certain roles by ID
					if ($decoded['id'] == 1) {
						return $fail("The selected role is not allowed.");
					}
				}
			],
			"user_pass" => [
				'string',
				'min:8',              // must be at least 8 characters in length
				'regex:/[a-z]/',      // must contain at least one lowercase letter
				'regex:/[A-Z]/',      // must contain at least one uppercase letter
				'regex:/[0-9]/',      // must contain at least one digit
				'regex:/[@$!%*#?&]/', // must contain a special character
			],
		];
	}

	public function messages(): array
	{
		return [
			"user_login.unique" => "The username has already been taken.",
			"user_email.email" => "The email field must be a valid email address.",
			"user_email.unique" => "The email has already been taken.",
			"user_pass.required" => "The password field is required."
		];
	}
}
