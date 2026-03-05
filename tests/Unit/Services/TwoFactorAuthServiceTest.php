<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TwoFactorAuthService;
use App\Models\User;
use App\Models\TwoFactorAuth;
use App\Helpers\PasswordHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class TwoFactorAuthServiceTest extends TestCase
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

        $this->service = new TwoFactorAuthService();
    }

    protected $user;
    protected $service;

    public function test_send_email_code()
    {
        Mail::fake();

        $result = $this->service->sendEmailCode($this->user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);

        // Verify 2FA record was created
        $twoFactorAuth = TwoFactorAuth::where('user_id', $this->user->id)->first();
        $this->assertNotNull($twoFactorAuth);
        $this->assertNotNull($twoFactorAuth->email_code);
        $this->assertNotNull($twoFactorAuth->email_code_expires_at);
    }

    public function test_verify_code_with_valid_code()
    {
        // Create 2FA record with valid code
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => '123456',
            'email_code_expires_at' => Carbon::now()->addMinutes(10),
            'is_enabled' => false,
            'backup_codes' => null,
            'last_used_at' => null,
        ]);

        $result = $this->service->verifyCode($this->user, '123456');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function test_verify_code_with_invalid_code()
    {
        // Create 2FA record with valid code
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => '123456',
            'email_code_expires_at' => Carbon::now()->addMinutes(10),
            'is_enabled' => false,
            'backup_codes' => null,
            'last_used_at' => null,
        ]);

        $result = $this->service->verifyCode($this->user, '654321');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    public function test_verify_code_with_expired_code()
    {
        // Create 2FA record with expired code
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => '123456',
            'email_code_expires_at' => Carbon::now()->subMinutes(1),
            'is_enabled' => false,
            'backup_codes' => null,
            'last_used_at' => null,
        ]);

        $result = $this->service->verifyCode($this->user, '123456');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }

    public function test_enable_two_factor()
    {
        $backupCodes = ['backup1', 'backup2', 'backup3'];

        $result = $this->service->enableTwoFactor($this->user, $backupCodes);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);

        // Verify 2FA is enabled
        $twoFactorAuth = TwoFactorAuth::where('user_id', $this->user->id)->first();
        $this->assertNotNull($twoFactorAuth);
        $this->assertTrue($twoFactorAuth->is_enabled);
        $this->assertEquals($backupCodes, $twoFactorAuth->backup_codes);
    }

    public function test_disable_two_factor()
    {
        // Create enabled 2FA
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => '123456',
            'email_code_expires_at' => Carbon::now()->addMinutes(10),
            'is_enabled' => true,
            'backup_codes' => ['backup1', 'backup2'],
            'last_used_at' => Carbon::now(),
        ]);

        $result = $this->service->disableTwoFactor($this->user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);

        // Verify 2FA is disabled
        $twoFactorAuth->refresh();
        $this->assertFalse($twoFactorAuth->is_enabled);
        $this->assertNull($twoFactorAuth->email_code);
        $this->assertNull($twoFactorAuth->backup_codes);
    }

    public function test_is_two_factor_enabled()
    {
        // Test when 2FA is not enabled
        $this->assertFalse($this->service->isTwoFactorEnabled($this->user));

        // Enable 2FA
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => null,
            'email_code_expires_at' => null,
            'is_enabled' => true,
            'backup_codes' => ['backup1', 'backup2'],
            'last_used_at' => null,
        ]);

        $this->assertTrue($this->service->isTwoFactorEnabled($this->user));
    }

    public function test_get_two_factor_status()
    {
        // Test when no 2FA record exists
        $status = $this->service->getTwoFactorStatus($this->user);
        $this->assertIsArray($status);
        $this->assertFalse($status['enabled']);

        // Create 2FA record
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => null,
            'email_code_expires_at' => null,
            'is_enabled' => true,
            'backup_codes' => ['backup1', 'backup2'],
            'last_used_at' => null,
        ]);

        $status = $this->service->getTwoFactorStatus($this->user);
        $this->assertIsArray($status);
        $this->assertTrue($status['enabled']);
        $this->assertArrayHasKey('backup_codes_count', $status);
    }

    public function test_generate_new_backup_codes()
    {
        // Create 2FA record
        $twoFactorAuth = TwoFactorAuth::create([
            'user_id' => $this->user->id,
            'email_code' => null,
            'email_code_expires_at' => null,
            'is_enabled' => true,
            'backup_codes' => ['old1', 'old2'],
            'last_used_at' => null,
        ]);

        $result = $this->service->generateNewBackupCodes($this->user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('backup_codes', $result);

        // Verify new backup codes were generated
        $twoFactorAuth->refresh();
        $this->assertNotEquals(['old1', 'old2'], $twoFactorAuth->backup_codes);
        $this->assertCount(10, $twoFactorAuth->backup_codes);
    }
}
