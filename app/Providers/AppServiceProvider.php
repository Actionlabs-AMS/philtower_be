<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use App\Services\OptionService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        
        // Dynamically configure timezone from database options
        $this->configureTimezoneFromDatabase();
        
        // Dynamically configure mail settings from database options
        // Primary: Database options, Fallback: Environment variables
        $this->configureMailFromDatabase();
    }

    /**
     * Configure timezone from database options with config fallback
     */
    protected function configureTimezoneFromDatabase(): void
    {
        try {
            // Check if options table exists (for migrations/initial setup)
            if (!Schema::hasTable('options')) {
                return;
            }

            $optionService = app(OptionService::class);
            $timezone = $optionService->getOption('timezone', config('app.timezone'));

            // Set timezone configuration
            if ($timezone) {
                Config::set('app.timezone', $timezone);
                date_default_timezone_set($timezone);
            }
        } catch (\Exception $e) {
            // If database connection fails or any error occurs,
            // fallback to config/app.php timezone setting
        }
    }

    /**
     * Configure mail settings from database options with .env fallback
     * 
     * Note: Sensitive email settings (passwords, API keys, secrets) are automatically
     * decrypted by the Option model when retrieved. The OptionService::getEmailConfig()
     * method handles this transparently - no manual decryption is needed here.
     */
    protected function configureMailFromDatabase(): void
    {
        try {
            // Check if options table exists (for migrations/initial setup)
            if (!Schema::hasTable('options')) {
                return;
            }

            $optionService = app(OptionService::class);
            // getEmailConfig() automatically decrypts sensitive values via Option model
            $mailConfig = $optionService->getEmailConfig();

            // Set mail configuration dynamically
            if (isset($mailConfig['default'])) {
                Config::set('mail.default', $mailConfig['default']);
            }

            if (isset($mailConfig['from'])) {
                Config::set('mail.from', $mailConfig['from']);
            }

            if (isset($mailConfig['mailers'])) {
                foreach ($mailConfig['mailers'] as $mailer => $config) {
                    Config::set("mail.mailers.{$mailer}", $config);
                }
            }

            if (isset($mailConfig['markdown'])) {
                Config::set('mail.markdown', $mailConfig['markdown']);
            }
        } catch (\Exception $e) {
            // If database connection fails or any error occurs,
            // fallback to default config/mail.php (which uses .env)
            // This ensures the application continues to work
        }
    }
}
