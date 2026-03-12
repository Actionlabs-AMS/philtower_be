<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use App\Services\OptionService;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use App\Helpers\MicrosoftGraphHelper;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email 
                            {email : The email address to send test email to}
                            {--type=simple : Type of test: simple, 2fa, or config}
                            {--user-id= : User ID for 2FA test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email sending functionality with SMTP configuration';

    protected $optionService;

    /**
     * Create a new command instance.
     */
    public function __construct(OptionService $optionService)
    {
        parent::__construct();
        $this->optionService = $optionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $type = $this->option('type');
        $userId = $this->option('user-id');

        $this->info('=== Email Testing Tool ===');
        $this->newLine();

        // Display current mail configuration
        $this->displayMailConfig();

        $this->newLine();

        switch ($type) {
            case 'config':
                $this->testConfig();
                break;
            case '2fa':
                if (!$userId) {
                    $this->error('User ID is required for 2FA test. Use --user-id=1');
                    return 1;
                }
                $this->test2FAEmail($email, $userId);
                break;
            case 'simple':
            default:
                $this->testSimpleEmail($email);
                break;
        }

        return 0;
    }

    /**
     * Display current mail configuration
     */
    protected function displayMailConfig()
    {
        $this->info('Current Mail Configuration:');
        $this->line('─────────────────────────────────────');
        
        // Get config from database (via OptionService)
        $mailConfig = $this->optionService->getEmailConfig();
        
        $this->table(
            ['Setting', 'Value'],
            [
                ['Default Mailer', $mailConfig['default'] ?? 'not set'],
                ['From Address', $mailConfig['from']['address'] ?? 'not set'],
                ['From Name', $mailConfig['from']['name'] ?? 'not set'],
                ['SMTP Host', $mailConfig['mailers']['smtp']['host'] ?? 'not set'],
                ['SMTP Port', $mailConfig['mailers']['smtp']['port'] ?? 'not set'],
                ['SMTP Encryption', $mailConfig['mailers']['smtp']['encryption'] ?? 'not set'],
                ['SMTP Username', $mailConfig['mailers']['smtp']['username'] ? '***configured***' : 'not set'],
                ['SMTP Password', $mailConfig['mailers']['smtp']['password'] ? '***configured***' : 'not set'],
            ]
        );
    }

    /**
     * Test simple email sending
     */
    protected function testSimpleEmail($email)
    {
        $this->info("Testing simple email to: {$email} (via Microsoft Graph)");
        $this->line('─────────────────────────────────────');

        try {
            $body = '<p>This is a test email from BaseCode. If you receive this, Microsoft Graph email is working correctly.</p>';
            MicrosoftGraphHelper::sendEmail($email, 'BaseCode Test Email', $body);

            $this->info('✓ Email sent successfully via Microsoft Graph!');
            $this->line("Check inbox: {$email}");
            return true;
        } catch (\Exception $e) {
            $this->error('✗ Failed to send email: ' . $e->getMessage());
            $this->newLine();
            $this->warn('Ensure Microsoft Graph is configured: MICROSOFT_TENANT_ID, MICROSOFT_CLIENT_ID, MICROSOFT_CLIENT_SECRET, MICROSOFT_SENDER_EMAIL in .env or System Settings > Email.');
            return false;
        }
    }

    /**
     * Test 2FA email sending (simulates login scenario)
     */
    protected function test2FAEmail($email, $userId)
    {
        $this->info("Testing 2FA email to: {$email} (User ID: {$userId})");
        $this->line('─────────────────────────────────────');

        try {
            $user = User::find($userId);
            
            if (!$user) {
                $this->error("User with ID {$userId} not found");
                return false;
            }

            if ($user->user_email !== $email) {
                $this->warn("Warning: User email ({$user->user_email}) doesn't match provided email ({$email})");
                $this->warn("Using user's email: {$user->user_email}");
            }

            // Generate a test code
            $testCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $this->line("Generated test code: {$testCode}");
            $this->newLine();

            $this->info('Sending via Microsoft Graph...');
            MicrosoftGraphService::sendTwoFactorCodeEmail($user, $testCode);
            $this->info('✓ Email sent via Microsoft Graph');
            $this->line("Check inbox: {$user->user_email}");
            $this->line("Test code: {$testCode}");
            return true;

        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test configuration only
     */
    protected function testConfig()
    {
        $this->info('Testing email configuration...');
        $this->line('─────────────────────────────────────');

        $mailConfig = $this->optionService->getEmailConfig();
        
        $issues = [];
        
        // Check required settings
        if (empty($mailConfig['default'])) {
            $issues[] = 'Default mailer is not set';
        }
        
        if (empty($mailConfig['from']['address'])) {
            $issues[] = 'From address is not set';
        }
        
        if ($mailConfig['default'] === 'smtp') {
            if (empty($mailConfig['mailers']['smtp']['host'])) {
                $issues[] = 'SMTP host is not set';
            }
            if (empty($mailConfig['mailers']['smtp']['port'])) {
                $issues[] = 'SMTP port is not set';
            }
            if (empty($mailConfig['mailers']['smtp']['username'])) {
                $issues[] = 'SMTP username is not set';
            }
            if (empty($mailConfig['mailers']['smtp']['password'])) {
                $issues[] = 'SMTP password is not set';
            }
        }
        
        if (empty($issues)) {
            $this->info('✓ Configuration looks good!');
            return true;
        } else {
            $this->error('✗ Configuration issues found:');
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }
            return false;
        }
    }
}

