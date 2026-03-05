<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailSettingsRequest extends FormRequest
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
        $rules = [
            'mailer' => 'required|string|in:smtp,mailgun,postmark,ses,microsoft,sendmail,log,array',
            'mail_from_name' => 'required|string|max:255',
            'mail_from_address' => 'required|email|max:255',
        ];

        // Only validate the selected mailer's configuration
        $mailer = $this->input('mailer');
        switch ($mailer) {
            case 'smtp':
                $rules['smtp'] = 'required|array';
                $rules['smtp.host'] = 'required|string|max:255';
                $rules['smtp.port'] = 'required|string|max:10';
                $rules['smtp.encryption'] = 'nullable|string|in:,tls,ssl,starttls';
                $rules['smtp.username'] = 'required|string|max:255';
                $rules['smtp.password'] = 'required|string|max:255';
                break;

            case 'mailgun':
                $rules['mailgun'] = 'required|array';
                $rules['mailgun.domain'] = 'required|string|max:255';
                $rules['mailgun.secret'] = 'required|string|max:255';
                break;

            case 'postmark':
                $rules['postmark'] = 'required|array';
                $rules['postmark.token'] = 'required|string|max:255';
                break;

            case 'ses':
                $rules['ses'] = 'required|array';
                $rules['ses.key'] = 'required|string|max:255';
                $rules['ses.secret'] = 'required|string|max:255';
                $rules['ses.region'] = 'required|string|max:50';
                break;

            case 'microsoft':
                $rules['microsoft'] = 'required|array';
                $rules['microsoft.tenant_id'] = 'required|string|max:255';
                $rules['microsoft.client_id'] = 'required|string|max:255';
                $rules['microsoft.client_secret'] = 'required|string|max:255';
                $rules['microsoft.sender_email'] = 'required|email|max:255';
                break;

            case 'sendmail':
            case 'log':
            case 'array':
                // These mailers don't need additional validation
                break;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mailer.required' => 'The mailer field is required.',
            'mailer.in' => 'The mailer must be one of: smtp, mailgun, postmark, ses, microsoft, sendmail, log, or array.',
            'mail_from_name.required' => 'The mail from name field is required.',
            'mail_from_name.max' => 'The mail from name may not be greater than 255 characters.',
            'mail_from_address.required' => 'The mail from address field is required.',
            'mail_from_address.email' => 'The mail from address must be a valid email address.',
            'smtp.required' => 'SMTP configuration is required when using SMTP mailer.',
            'smtp.host.required' => 'SMTP host is required.',
            'smtp.port.required' => 'SMTP port is required.',
            'smtp.username.required' => 'SMTP username is required.',
            'smtp.password.required' => 'SMTP password is required.',
            'mailgun.required' => 'Mailgun configuration is required when using Mailgun mailer.',
            'mailgun.domain.required' => 'Mailgun domain is required.',
            'mailgun.secret.required' => 'Mailgun secret is required.',
            'postmark.required' => 'Postmark configuration is required when using Postmark mailer.',
            'postmark.token.required' => 'Postmark token is required.',
            'ses.required' => 'SES configuration is required when using SES mailer.',
            'ses.key.required' => 'SES key is required.',
            'ses.secret.required' => 'SES secret is required.',
            'ses.region.required' => 'SES region is required.',
            'microsoft.required' => 'Microsoft configuration is required when using Microsoft mailer.',
            'microsoft.tenant_id.required' => 'Microsoft tenant ID is required.',
            'microsoft.client_id.required' => 'Microsoft client ID is required.',
            'microsoft.client_secret.required' => 'Microsoft client secret is required.',
            'microsoft.sender_email.required' => 'Microsoft sender email is required.',
            'microsoft.sender_email.email' => 'Microsoft sender email must be a valid email address.',
        ];
    }
}

