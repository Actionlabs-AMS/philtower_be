<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class PasswordHelper 
{
    /**
     * Generate cryptographically secure salt
     * 
     * @return string
     */
    public static function generateSalt(): string 
    {
        // Use cryptographically secure random bytes
        return bin2hex(random_bytes(32)); // 64 character hex string
    }

    /**
     * Generate secure password hash with salt + pepper
     * 
     * @param string $salt
     * @param string $password
     * @return string
     */
    public static function generatePassword(string $salt, string $password): string 
    {
        // Use application key as pepper (more secure than env)
        $pepper = config('app.key');
        
        // Combine salt + password + pepper (same order as in login verification)
        $combined = $salt . $password . $pepper;
        
        // Use Laravel's bcrypt with high cost factor
        return Hash::make($combined, [
            'rounds' => 12 // Higher than default 10
        ]);
    }

    /**
     * Verify password with salt + pepper
     * 
     * @param string $password
     * @param string $salt
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $salt, string $hash): bool 
    {
        // Use application key as pepper (same as in password generation)
        $pepper = config('app.key');
        
        // Combine salt + password + pepper (same order as in password generation)
        $combined = $salt . $password . $pepper;
        
        return Hash::check($combined, $hash);
    }

    /**
     * Generate password reset token
     * 
     * @return string
     */
    public static function generateResetToken(): string 
    {
        return Str::random(64);
    }

    /**
     * Hash password reset token for storage
     * 
     * @param string $token
     * @return string
     */
    public static function hashResetToken(string $token): string 
    {
        return Hash::make($token);
    }

    /**
     * Verify password reset token
     * 
     * @param string $token
     * @param string $hashedToken
     * @return bool
     */
    public static function verifyResetToken(string $token, string $hashedToken): bool 
    {
        return Hash::check($token, $hashedToken);
    }

    /**
     * Generate secure temporary password
     * 
     * @return string
     */
    public static function generateTemporaryPassword(): string 
    {
        // Generate cryptographically secure password
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < 12; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }

    /**
     * Check password strength
     * 
     * @param string $password
     * @return array
     */
    public static function checkPasswordStrength(string $password): array 
    {
        $strength = 0;
        $feedback = [];

        // Length check
        if (strlen($password) >= 8) {
            $strength += 1;
        } else {
            $feedback[] = 'Password must be at least 8 characters long';
        }

        // Uppercase check
        if (preg_match('/[A-Z]/', $password)) {
            $strength += 1;
        } else {
            $feedback[] = 'Password must contain at least one uppercase letter';
        }

        // Lowercase check
        if (preg_match('/[a-z]/', $password)) {
            $strength += 1;
        } else {
            $feedback[] = 'Password must contain at least one lowercase letter';
        }

        // Number check
        if (preg_match('/[0-9]/', $password)) {
            $strength += 1;
        } else {
            $feedback[] = 'Password must contain at least one number';
        }

        // Special character check
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $strength += 1;
        } else {
            $feedback[] = 'Password must contain at least one special character';
        }

        // Common password check
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey'
        ];

        if (in_array(strtolower($password), $commonPasswords)) {
            $strength = 0;
            $feedback[] = 'Password is too common, please choose a stronger password';
        }

        return [
            'strength' => $strength,
            'is_strong' => $strength >= 4,
            'feedback' => $feedback
        ];
    }
}
