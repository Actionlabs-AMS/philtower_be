<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebhookTriggerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $token = $this->header('X-Webhook-Token');
        $expectedToken = config('backup.webhook_token', env('BACKUP_WEBHOOK_TOKEN'));

        return $token && $token === $expectedToken;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // No body validation needed, only header token validation
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization(): void
    {
        abort(401, 'Unauthorized: Invalid or missing webhook token.');
    }
}

