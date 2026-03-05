<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
			"user_email" => "required|email|unique:users,user_email,".$this->id,
			"user_pass" => [
				'string',
				'min:8',              // must be at least 8 characters in length
				'regex:/[a-z]/',      // must contain at least one lowercase letter
				'regex:/[A-Z]/',      // must contain at least one uppercase letter
				'regex:/[0-9]/',      // must contain at least one digit
				'regex:/[@$!%*#?&]/', // must contain a special character
			],
			"first_name" => "nullable|regex:/^[a-zA-Z0-9,&-_\s]+$/",
			"last_name" => "nullable|regex:/^[a-zA-Z0-9,&-_\s]+$/",
			"nickname" => "nullable|regex:/^[a-zA-Z0-9,&-_\s]+$/",
			"biography" => "nullable|regex:/^[a-zA-Z0-9,&-_\s]+$/",
		];
	}

	public function messages(): array
	{
		return [
			'user_email.required' => 'The email address is required.',
			'user_email.email' => 'Please enter a valid email address.',
			'user_email.unique' => 'This email address is already registered.',
			'user_pass.min' => 'The password must be at least 8 characters.',
			'user_pass.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
			'first_name.regex' => 'The first name can only contain letters, numbers, and special characters (, & - _).',
			'last_name.regex' => 'The last name can only contain letters, numbers, and special characters (, & - _).',
			'nickname.regex' => 'The nickname can only contain letters, numbers, and special characters (, & - _).',
			'biography.regex' => 'The biography can only contain letters, numbers, and special characters (, & - _).'
		];
	}
}
