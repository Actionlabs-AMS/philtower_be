<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class TwoFactorAuth extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_code',
        'email_code_expires_at',
        'is_enabled',
        'backup_codes',
        'last_used_at',
    ];

    protected $casts = [
        'email_code_expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_enabled' => 'boolean',
        'backup_codes' => 'array',
    ];

    protected $hidden = [
        'email_code',
        'backup_codes',
    ];

    /**
     * Generate a 6-digit email verification code
     */
    public static function generateEmailCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate backup codes
     */
    public static function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(substr(md5(random_bytes(16)), 0, 8));
        }
        return $codes;
    }

    /**
     * Check if email code is valid and not expired
     */
    public function isEmailCodeValid(string $code): bool
    {
        if (!$this->email_code || !$this->email_code_expires_at) {
            return false;
        }

        if ($this->email_code_expires_at->isPast()) {
            return false;
        }

        return hash_equals($this->email_code, $code);
    }

    /**
     * Check if backup code is valid
     */
    public function isBackupCodeValid(string $code): bool
    {
        if (!$this->backup_codes) {
            return false;
        }

        $index = array_search($code, $this->backup_codes);
        if ($index !== false) {
            // Remove used backup code
            unset($this->backup_codes[$index]);
            $this->backup_codes = array_values($this->backup_codes);
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Generate new email code
     */
    public function generateNewEmailCode(): string
    {
        $code = self::generateEmailCode();
        $this->update([
            'email_code' => $code,
            'email_code_expires_at' => now()->addMinutes(10), // 10 minutes expiry
        ]);
        return $code;
    }

    /**
     * Enable 2FA for user
     */
    public function enable(): void
    {
        $this->update([
            'is_enabled' => true,
            'backup_codes' => self::generateBackupCodes(),
        ]);
    }

    /**
     * Disable 2FA for user
     */
    public function disable(): void
    {
        $this->update([
            'is_enabled' => false,
            'email_code' => null,
            'email_code_expires_at' => null,
            'backup_codes' => null,
        ]);
    }

    /**
     * Get user relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if 2FA is required for user
     */
    public static function isRequiredForUser(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->where('is_enabled', true)
            ->exists();
    }

    /**
     * Get remaining backup codes count
     */
    public function getRemainingBackupCodesCount(): int
    {
        return count($this->backup_codes ?? []);
    }

    /**
     * Check if user has any backup codes left
     */
    public function hasBackupCodes(): bool
    {
        return $this->getRemainingBackupCodesCount() > 0;
    }
}
