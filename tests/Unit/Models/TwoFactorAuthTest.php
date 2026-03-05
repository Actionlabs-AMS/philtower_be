<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\TwoFactorAuth;
use App\Models\User;
use App\Helpers\PasswordHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::create([
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'user_pass' => PasswordHelper::generatePassword('salt123', 'password123'),
            'user_salt' => 'salt123',
            'user_status' => 1,
        ]);
    }

    protected $user;

    public function test_two_factor_auth_can_be_created()
    {
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => '123456',
            'email_code_expires_at' => Carbon::now()->addMinutes(10),
            'is_enabled' => false,
            'backup_codes' => ['backup1', 'backup2'],
            'last_used_at' => null,
        ]);

        $this->assertInstanceOf(TwoFactorAuth::class, $twoFactorAuth);
        $this->assertEquals($this->user->id, $twoFactorAuth->user_id);
        $this->assertEquals('123456', $twoFactorAuth->email_code);
    }

    public function test_generate_email_code_static_method()
    {
        $code = TwoFactorAuth::generateEmailCode();
        
        $this->assertIsString($code);
        $this->assertEquals(6, strlen($code));
        $this->assertTrue(is_numeric($code));
    }

    public function test_generate_backup_codes_static_method()
    {
        $codes = TwoFactorAuth::generateBackupCodes();
        
        $this->assertIsArray($codes);
        $this->assertCount(10, $codes);
        
        foreach ($codes as $code) {
            $this->assertIsString($code);
            $this->assertEquals(8, strlen($code));
        }
    }

    public function test_validate_code_method()
    {
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => '123456',
            'email_code_expires_at' => Carbon::now()->addMinutes(10),
            'is_enabled' => false,
            'backup_codes' => null,
            'last_used_at' => null,
        ]);

        // Test valid code
        $this->assertTrue($twoFactorAuth->validateCode('123456'));
        
        // Test invalid code
        $this->assertFalse($twoFactorAuth->validateCode('654321'));
        
        // Test expired code
        $twoFactorAuth->email_code_expires_at = Carbon::now()->subMinutes(1);
        $twoFactorAuth->save();
        $this->assertFalse($twoFactorAuth->validateCode('123456'));
    }

    public function test_validate_backup_code_method()
    {
        $backupCodes = ['backup1', 'backup2', 'backup3'];
        
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => null,
            'email_code_expires_at' => null,
            'is_enabled' => true,
            'backup_codes' => $backupCodes,
            'last_used_at' => null,
        ]);

        // Test valid backup code
        $this->assertTrue($twoFactorAuth->validateBackupCode('backup1'));
        
        // Test invalid backup code
        $this->assertFalse($twoFactorAuth->validateBackupCode('invalid'));
    }

    public function test_enable_two_factor_method()
    {
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => null,
            'email_code_expires_at' => null,
            'is_enabled' => false,
            'backup_codes' => null,
            'last_used_at' => null,
        ]);

        $backupCodes = ['backup1', 'backup2'];
        $twoFactorAuth->enableTwoFactor($backupCodes);

        $this->assertTrue($twoFactorAuth->is_enabled);
        $this->assertEquals($backupCodes, $twoFactorAuth->backup_codes);
    }

    public function test_disable_two_factor_method()
    {
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => '123456',
            'email_code_expires_at' => Carbon::now()->addMinutes(10),
            'is_enabled' => true,
            'backup_codes' => ['backup1', 'backup2'],
            'last_used_at' => Carbon::now(),
        ]);

        $twoFactorAuth->disableTwoFactor();

        $this->assertFalse($twoFactorAuth->is_enabled);
        $this->assertNull($twoFactorAuth->email_code);
        $this->assertNull($twoFactorAuth->backup_codes);
    }

    public function test_use_backup_code_method()
    {
        $backupCodes = ['backup1', 'backup2', 'backup3'];
        
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => null,
            'email_code_expires_at' => null,
            'is_enabled' => true,
            'backup_codes' => $backupCodes,
            'last_used_at' => null,
        ]);

        $result = $twoFactorAuth->useBackupCode('backup1');
        
        $this->assertTrue($result);
        $this->assertNotContains('backup1', $twoFactorAuth->fresh()->backup_codes);
        $this->assertNotNull($twoFactorAuth->fresh()->last_used_at);
    }

    public function test_use_invalid_backup_code()
    {
        $backupCodes = ['backup1', 'backup2'];
        
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => null,
            'email_code_expires_at' => null,
            'is_enabled' => true,
            'backup_codes' => $backupCodes,
            'last_used_at' => null,
        ]);

        $result = $twoFactorAuth->useBackupCode('invalid');
        
        $this->assertFalse($result);
        $this->assertEquals($backupCodes, $twoFactorAuth->fresh()->backup_codes);
    }

    public function test_two_factor_auth_casts()
    {
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => '123456',
            'email_code_expires_at' => Carbon::now()->addMinutes(10),
            'is_enabled' => true,
            'backup_codes' => ['backup1', 'backup2'],
            'last_used_at' => Carbon::now(),
        ]);

        $this->assertIsBool($twoFactorAuth->is_enabled);
        $this->assertIsArray($twoFactorAuth->backup_codes);
        $this->assertInstanceOf(\Carbon\Carbon::class, $twoFactorAuth->email_code_expires_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $twoFactorAuth->last_used_at);
    }
}
