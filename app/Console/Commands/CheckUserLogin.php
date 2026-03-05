<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Helpers\PasswordHelper;

class CheckUserLogin extends Command
{
    protected $signature = 'user:check-login {email} {password?}';
    protected $description = 'Check user login credentials and status';

    public function handle()
    {
        $email = $this->argument('email');
        $testPassword = $this->argument('password') ?? 'password123';

        $user = User::where('user_email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        $this->info("User found:");
        $this->line("  ID: {$user->id}");
        $this->line("  Login: {$user->user_login}");
        $this->line("  Email: {$user->user_email}");
        $this->line("  Status: {$user->user_status} " . ($user->user_status == 1 ? '(Active)' : ($user->user_status == 0 ? '(Inactive)' : '(Suspended)')));
        $this->line("  Role ID: {$user->role_id}");
        $this->line("  Has Salt: " . ($user->user_salt ? 'Yes' : 'No'));
        $this->line("  Has Password: " . ($user->user_pass ? 'Yes' : 'No'));

        if ($user->user_salt && $user->user_pass) {
            $this->line("\nTesting password verification:");
            $isValid = PasswordHelper::verifyPassword($testPassword, $user->user_salt, $user->user_pass);
            
            if ($isValid) {
                $this->info("  ✓ Password '{$testPassword}' is VALID");
            } else {
                $this->error("  ✗ Password '{$testPassword}' is INVALID");
                $this->line("\nAttempting to reset password...");
                $salt = PasswordHelper::generateSalt();
                $hashedPassword = PasswordHelper::generatePassword($salt, 'password123');
                $user->user_salt = $salt;
                $user->user_pass = $hashedPassword;
                $user->user_status = 1; // Set to active
                $user->save();
                $this->info("  ✓ Password reset successfully!");
                $this->info("  ✓ User status set to Active (1)");
                
                // Verify the new password works
                $isValidNow = PasswordHelper::verifyPassword('password123', $user->user_salt, $user->user_pass);
                if ($isValidNow) {
                    $this->info("  ✓ Password verification confirmed!");
                } else {
                    $this->error("  ✗ Password verification still failing after reset!");
                }
            }
        } else {
            $this->warn("  User is missing salt or password hash!");
        }

        return 0;
    }
}

