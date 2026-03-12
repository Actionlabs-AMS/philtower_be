<?php

namespace App\Services;

use App\Models\User;
use App\Models\TwoFactorAuth;
use App\Services\MicrosoftGraphService;
use App\Services\OptionService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TwoFactorAuthService
{
    protected $optionService;

    public function __construct(OptionService $optionService = null)
    {
        $this->optionService = $optionService ?? app(OptionService::class);
    }

    /**
     * Check if 2FA is enabled system-wide
     */
    public function isTwoFactorEnabledSystemWide(): bool
    {
        return $this->optionService->getOption('two_factor_enabled', false);
    }

    /**
     * Check if 2FA is required system-wide
     */
    public function isTwoFactorRequiredSystemWide(): bool
    {
        return $this->optionService->getOption('two_factor_required', false);
    }

    /**
     * Check if 2FA should be enforced for a user (system-wide required OR user has it enabled)
     */
    public function isTwoFactorRequiredForUser(User $user): bool
    {
        // If system-wide 2FA is required, enforce it
        if ($this->isTwoFactorRequiredSystemWide()) {
            return true;
        }

        // If system-wide 2FA is enabled but not required, check user's individual setting
        if ($this->isTwoFactorEnabledSystemWide()) {
            return $this->isTwoFactorEnabled($user);
        }

        // System-wide 2FA is disabled
        return false;
    }

    /**
     * Send 2FA code via email
     */
    public function sendEmailCode(User $user): array
    {
        try {
            // Get or create 2FA record
            $twoFactorAuth = TwoFactorAuth::firstOrCreate(
                ['user_id' => $user->id],
                ['is_enabled' => false]
            );

            // Generate new code
            $code = $twoFactorAuth->generateNewEmailCode();

            $emailSent = false;
            $lastError = null;

            try {
                MicrosoftGraphService::sendTwoFactorCodeEmail($user, $code);
                $emailSent = true;
                Log::info('2FA code sent via Microsoft Graph', [
                    'user_id' => $user->id,
                    'email' => $this->anonymizeEmail($user->user_email),
                ]);
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error('Microsoft Graph failed to send 2FA code', [
                    'user_id' => $user->id,
                    'email' => $this->anonymizeEmail($user->user_email),
                    'error' => $lastError,
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            if (!$emailSent) {
                return [
                    'success' => false,
                    'message' => 'Failed to send verification code. Please check your email configuration.',
                    'error' => $lastError,
                ];
            }

            // Log successful action
            Log::info('2FA email code sent successfully', [
                'user_id' => $user->id,
                'email' => $this->anonymizeEmail($user->user_email),
                'expires_at' => $twoFactorAuth->email_code_expires_at,
            ]);

            return [
                'success' => true,
                'message' => 'Verification code sent to your email',
                'expires_in' => 600, // 10 minutes
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send 2FA email code - unexpected error', [
                'user_id' => $user->id,
                'email' => $this->anonymizeEmail($user->user_email),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send verification code. Please try again.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode(User $user, string $code): array
    {
        try {
            $twoFactorAuth = TwoFactorAuth::where('user_id', $user->id)->first();

            if (!$twoFactorAuth) {
                return [
                    'success' => false,
                    'message' => 'Two-factor authentication not set up for this user',
                ];
            }

            // Check if code is valid
            if ($twoFactorAuth->isEmailCodeValid($code)) {
                // Update last used timestamp
                $twoFactorAuth->update(['last_used_at' => now()]);

                Log::info('2FA code verified successfully', [
                    'user_id' => $user->id,
                    'email' => $this->anonymizeEmail($user->user_email),
                ]);

                return [
                    'success' => true,
                    'message' => 'Verification successful',
                ];
            }

            // Check backup codes
            if ($twoFactorAuth->isBackupCodeValid($code)) {
                Log::info('2FA backup code used', [
                    'user_id' => $user->id,
                    'email' => $this->anonymizeEmail($user->user_email),
                    'remaining_codes' => $twoFactorAuth->getRemainingBackupCodesCount(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Verification successful',
                    'backup_code_used' => true,
                    'remaining_backup_codes' => $twoFactorAuth->getRemainingBackupCodesCount(),
                ];
            }

            Log::warning('Invalid 2FA code attempt', [
                'user_id' => $user->id,
                'email' => $this->anonymizeEmail($user->user_email),
            ]);

            return [
                'success' => false,
                'message' => 'Invalid verification code',
            ];

        } catch (\Exception $e) {
            Log::error('2FA verification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Verification failed. Please try again.',
            ];
        }
    }

    /**
     * Enable 2FA for user
     */
    public function enableTwoFactor(User $user): array
    {
        try {
            // Check if 2FA is enabled system-wide
            if (!$this->isTwoFactorEnabledSystemWide()) {
                return [
                    'success' => false,
                    'message' => 'Two-factor authentication is not enabled system-wide. Please contact your administrator.',
                ];
            }

            $twoFactorAuth = TwoFactorAuth::firstOrCreate(
                ['user_id' => $user->id],
                ['is_enabled' => false]
            );

            $twoFactorAuth->enable();

            Log::info('2FA enabled for user', [
                'user_id' => $user->id,
                'email' => $this->anonymizeEmail($user->user_email),
            ]);

            return [
                'success' => true,
                'message' => 'Two-factor authentication enabled successfully',
                'backup_codes' => $twoFactorAuth->backup_codes,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to enable 2FA', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to enable two-factor authentication',
            ];
        }
    }

    /**
     * Disable 2FA for user
     */
    public function disableTwoFactor(User $user): array
    {
        try {
            // Check if 2FA is required system-wide - users cannot disable if required
            if ($this->isTwoFactorRequiredSystemWide()) {
                return [
                    'success' => false,
                    'message' => 'Two-factor authentication is required system-wide and cannot be disabled.',
                ];
            }

            $twoFactorAuth = TwoFactorAuth::where('user_id', $user->id)->first();

            if (!$twoFactorAuth) {
                return [
                    'success' => false,
                    'message' => 'Two-factor authentication not enabled',
                ];
            }

            $twoFactorAuth->disable();

            Log::info('2FA disabled for user', [
                'user_id' => $user->id,
                'email' => $this->anonymizeEmail($user->user_email),
            ]);

            return [
                'success' => true,
                'message' => 'Two-factor authentication disabled successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to disable 2FA', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to disable two-factor authentication',
            ];
        }
    }

    /**
     * Check if 2FA is enabled for user
     */
    public function isTwoFactorEnabled(User $user): bool
    {
        return TwoFactorAuth::where('user_id', $user->id)
            ->where('is_enabled', true)
            ->exists();
    }

    /**
     * Get 2FA status for user
     */
    public function getTwoFactorStatus(User $user): array
    {
        $twoFactorAuth = TwoFactorAuth::where('user_id', $user->id)->first();

        if (!$twoFactorAuth) {
            return [
                'enabled' => false,
                'backup_codes_count' => 0,
                'last_used' => null,
            ];
        }

        return [
            'enabled' => $twoFactorAuth->is_enabled,
            'backup_codes_count' => $twoFactorAuth->getRemainingBackupCodesCount(),
            'last_used' => $twoFactorAuth->last_used_at,
        ];
    }

    /**
     * Generate new backup codes
     */
    public function generateNewBackupCodes(User $user): array
    {
        try {
            $twoFactorAuth = TwoFactorAuth::where('user_id', $user->id)->first();

            if (!$twoFactorAuth || !$twoFactorAuth->is_enabled) {
                return [
                    'success' => false,
                    'message' => 'Two-factor authentication not enabled',
                ];
            }

            $newCodes = TwoFactorAuth::generateBackupCodes();
            $twoFactorAuth->update(['backup_codes' => $newCodes]);

            Log::info('New backup codes generated', [
                'user_id' => $user->id,
                'email' => $this->anonymizeEmail($user->user_email),
            ]);

            return [
                'success' => true,
                'message' => 'New backup codes generated',
                'backup_codes' => $newCodes,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to generate backup codes', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate backup codes',
            ];
        }
    }

    /**
     * Anonymize email for logging
     */
    private function anonymizeEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***';
        }

        $username = $parts[0];
        $domain = $parts[1];

        if (strlen($username) <= 2) {
            $anonymizedUsername = str_repeat('*', strlen($username));
        } else {
            $anonymizedUsername = $username[0] . str_repeat('*', strlen($username) - 2) . substr($username, -1);
        }

        return $anonymizedUsername . '@' . $domain;
    }
}
