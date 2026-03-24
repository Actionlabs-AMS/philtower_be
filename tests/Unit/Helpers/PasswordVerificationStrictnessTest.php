<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PasswordHelper;
use Tests\TestCase;

class PasswordVerificationStrictnessTest extends TestCase
{
    public function test_verify_password_rejects_partial_password_prefix(): void
    {
        $salt = PasswordHelper::generateSalt();
        $fullPassword = 'Password123!';
        $partialPassword = 'Password';
        $hash = PasswordHelper::generatePassword($salt, $fullPassword);

        $this->assertTrue(PasswordHelper::verifyPassword($fullPassword, $salt, $hash));
        $this->assertFalse(PasswordHelper::verifyPassword($partialPassword, $salt, $hash));
    }
}
