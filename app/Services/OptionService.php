<?php

namespace App\Services;

use App\Models\Option;
use App\Http\Resources\OptionResource;
use Illuminate\Support\Facades\Cache;

class OptionService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new OptionResource(new Option()), new Option());
    }

    /**
     * Get all options with caching
     */
    public function getAllOptions()
    {
        return Cache::remember('options.all', 3600, function () {
            return Option::all()->mapWithKeys(function ($option) {
                return [$option->option_key => $option->value];
            });
        });
    }

    /**
     * Get option by key with caching
     */
    public function getOption($key, $default = null)
    {
        return Cache::remember("option.{$key}", 3600, function () use ($key, $default) {
            return Option::get($key, $default);
        });
    }

    /**
     * Set option by key and clear cache
     */
    public function setOption($key, $value, $type = 'string', $description = null)
    {
        // Handle null values directly - delete the option
        if ($value === null) {
            Option::where('option_key', $key)->delete();
            Cache::forget("option.{$key}");
            Cache::forget('options.all');
            Cache::forget('general.settings');
            return null;
        }
        
        $option = Option::set($key, $value, $type, $description);
        
        // Clear related caches (always clear, even if option was deleted/null)
        Cache::forget("option.{$key}");
        Cache::forget('options.all');
        
        // Clear mail config cache if this is a mail-related option
        if (strpos($key, 'mail_') === 0 || 
            strpos($key, 'mailgun_') === 0 || 
            strpos($key, 'postmark_') === 0 || 
            strpos($key, 'ses_') === 0 || 
            strpos($key, 'microsoft_') === 0) {
            Cache::forget('mail.config');
        }
        
        return $this->resource::make($option);
    }

    /**
     * Update multiple options at once
     */
    public function updateMultiple(array $options)
    {
        $results = [];
        
        foreach ($options as $key => $data) {
            $value = $data['value'] ?? $data;
            $type = $data['type'] ?? 'string';
            $description = $data['description'] ?? null;
            
            $results[$key] = $this->setOption($key, $value, $type, $description);
        }
        
        return $results;
    }

    /**
     * Get options by category/group
     */
    public function getOptionsByGroup($group)
    {
        return Cache::remember("options.group.{$group}", 3600, function () use ($group) {
            return Option::where('option_key', 'like', "{$group}_%")
                ->get()
                ->mapWithKeys(function ($option) {
                    return [$option->option_key => $option->value];
                });
        });
    }

    /**
     * Delete option by key
     */
    public function deleteOption($key)
    {
        $option = Option::where('option_key', $key)->first();
        
        if ($option) {
            $option->delete();
            
            // Clear related caches
            Cache::forget("option.{$key}");
            Cache::forget('options.all');
            
            return true;
        }
        
        return false;
    }

    /**
     * Get system settings (2FA, security, etc.)
     */
    public function getSystemSettings()
    {
        $settings = [
            'two_factor' => [
                'enabled' => $this->getOption('two_factor_enabled', false),
                'required' => $this->getOption('two_factor_required', false),
                'backup_codes_count' => $this->getOption('two_factor_backup_codes_count', 10),
            ],
            'security' => [
                'session_timeout' => $this->getOption('session_timeout', 30),
                'max_login_attempts' => $this->getOption('max_login_attempts', 5),
                'password_min_length' => $this->getOption('password_min_length', 8),
            ],
            'general' => [
                'site_name' => $this->getOption('site_name', 'CorePanel'),
                'site_description' => $this->getOption('site_description', 'Admin Panel'),
                'timezone' => $this->getOption('timezone', 'Asia/Manila'),
            ],
        ];

        return $settings;
    }

    /**
     * Get general settings only (excludes security and 2FA)
     */
    public function getGeneralSettings()
    {
        // Helper function to convert storage path to full URL
        $getLogoUrl = function($logoPath) {
            if (empty($logoPath)) {
                return '';
            }
            
            // If it's already a full URL, return as is
            if (filter_var($logoPath, FILTER_VALIDATE_URL)) {
                return $logoPath;
            }
            
            // Convert storage path to full URL
            // storage/logos/filename.png -> http://domain.com/storage/logos/filename.png
            // Use request to get current URL with port, fallback to config
            try {
                // Try to get URL from current request (includes port automatically)
                if (app()->runningInConsole() === false && request()) {
                    // request()->root() returns the full base URL including port
                    $baseUrl = rtrim(request()->root(), '/');
                } else {
                    // Fallback to config (reads from APP_URL in .env)
                    $baseUrl = rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
                }
            } catch (\Exception $e) {
                // If request is not available, use config
                $baseUrl = rtrim(config('app.url', 'http://127.0.0.1:8000'), '/');
            }
            
            // Remove leading slash if present
            $cleanPath = ltrim($logoPath, '/');
            
            // Ensure path starts with storage/
            if (strpos($cleanPath, 'storage/') !== 0) {
                $cleanPath = 'storage/' . $cleanPath;
            }
            
            // Build full URL
            $fullUrl = $baseUrl . '/' . $cleanPath;
            
            return $fullUrl;
        };
        
        // Get logo values - return null if option doesn't exist
        $authLogo = $this->getOption('auth_logo', null);
        $sidenavLogo = $this->getOption('sidenav_logo', null);
        
        $settings = [
            'site' => [
                'site_name' => $this->getOption('site_name', 'CorePanel'),
                'site_description' => $this->getOption('site_description', 'Admin Panel Management System'),
                'auth_logo' => $authLogo ? $getLogoUrl($authLogo) : null,
                'sidenav_logo' => $sidenavLogo ? $getLogoUrl($sidenavLogo) : null,
            ],
            'date_time' => [
                'timezone' => $this->getOption('timezone', 'Asia/Manila'),
                'date_format' => $this->getOption('date_format', 'Y-m-d'),
                'time_format' => $this->getOption('time_format', 'H:i:s'),
            ],
            'language' => [
                'default_language' => $this->getOption('default_language', 'en'),
            ],
        ];

        return $settings;
    }

    /**
     * Update general settings only
     */
    public function updateGeneralSettings(array $settings)
    {
        $results = [];
        
        // Site settings
        if (isset($settings['site'])) {
            foreach ($settings['site'] as $key => $value) {
                // Handle null values for logo removal - setOption will handle deletion
                $results[$key] = $this->setOption($key, $value, 'string');
            }
        }
        
        // Date/Time settings
        if (isset($settings['date_time'])) {
            foreach ($settings['date_time'] as $key => $value) {
                $results[$key] = $this->setOption($key, $value, 'string');
            }
        }
        
        // Language settings
        if (isset($settings['language'])) {
            foreach ($settings['language'] as $key => $value) {
                $results[$key] = $this->setOption($key, $value, 'string');
            }
        }
        
        // Clear all option caches to ensure fresh data
        Cache::forget('options.all');
        foreach (array_keys($results) as $key) {
            Cache::forget("option.{$key}");
        }
        
        // Also clear cache for logo fields if they were in the settings (even if deleted)
        if (isset($settings['site'])) {
            if (array_key_exists('auth_logo', $settings['site'])) {
                Cache::forget("option.auth_logo");
            }
            if (array_key_exists('sidenav_logo', $settings['site'])) {
                Cache::forget("option.sidenav_logo");
            }
        }
        
        return $results;
    }

    /**
     * Get email settings
     */
    public function getEmailSettings()
    {
        $settings = [
            'mailer' => $this->getOption('mail_mailer', 'smtp'),
            'mail_from_name' => $this->getOption('mail_from_name', 'CorePanel'),
            'mail_from_address' => $this->getOption('mail_from_address', 'noreply@example.com'),
            // SMTP settings
            'smtp' => [
                'host' => $this->getOption('mail_host', ''),
                'port' => $this->getOption('mail_port', '587'),
                'encryption' => $this->getOption('mail_encryption', 'tls'),
                'username' => $this->getOption('mail_username', ''),
                'password' => $this->getOption('mail_password', ''),
            ],
            // Mailgun settings
            'mailgun' => [
                'domain' => $this->getOption('mailgun_domain', ''),
                'secret' => $this->getOption('mailgun_secret', ''),
            ],
            // Postmark settings
            'postmark' => [
                'token' => $this->getOption('postmark_token', ''),
            ],
            // SES settings
            'ses' => [
                'key' => $this->getOption('ses_key', ''),
                'secret' => $this->getOption('ses_secret', ''),
                'region' => $this->getOption('ses_region', 'us-east-1'),
            ],
            // Microsoft Graph settings
            'microsoft' => [
                'tenant_id' => $this->getOption('microsoft_tenant_id', ''),
                'client_id' => $this->getOption('microsoft_client_id', ''),
                'client_secret' => $this->getOption('microsoft_client_secret', ''),
                'sender_email' => $this->getOption('microsoft_sender_email', ''),
            ],
        ];

        return $settings;
    }

    /**
     * Get email configuration for Laravel Mail config
     * Primary: Database options, Fallback: Environment variables
     * 
     * @return array
     */
    public function getEmailConfig()
    {
        $service = $this;
        // On Windows, sendmail is not available; clear cached config if it was sendmail so we recompute with 'log'
        if (PHP_OS_FAMILY === 'Windows' && $this->getOption('mail_mailer', '') === 'sendmail') {
            Cache::forget('mail.config');
        }
        return Cache::remember('mail.config', 3600, function () use ($service) {
            // Helper function to get option with env fallback
            $getConfig = function ($optionKey, $envKey, $default = null) use ($service) {
                try {
                    $dbValue = $service->getOption($optionKey, null);
                    if ($dbValue !== null && $dbValue !== '') {
                        return $dbValue;
                    }
                } catch (\Exception $e) {
                    // If database query fails, fallback to env
                }
                return env($envKey, $default);
            };

            // Get mailer (default mailer)
            // Note: "microsoft" is not a Laravel mailer - it's handled via MicrosoftGraphService
            // If microsoft is selected, use SMTP as fallback for Laravel Mail
            $mailer = $getConfig('mail_mailer', 'MAIL_MAILER', 'smtp');
            if ($mailer === 'microsoft') {
                $mailer = 'smtp'; // Use SMTP for Laravel Mail, Microsoft Graph handled separately
            }
            // On Windows, sendmail is not available (/usr/sbin/sendmail); use log to avoid runtime errors
            if ($mailer === 'sendmail' && PHP_OS_FAMILY === 'Windows') {
                $mailer = 'log';
            }

            // Get from address and name
            $fromAddress = $getConfig('mail_from_address', 'MAIL_FROM_ADDRESS', 'hello@example.com');
            $fromName = $getConfig('mail_from_name', 'MAIL_FROM_NAME', 'Example');

            // Build mailers configuration
            $mailers = [
                'smtp' => [
                    'transport' => 'smtp',
                    'url' => env('MAIL_URL'),
                    'host' => $getConfig('mail_host', 'MAIL_HOST', 'smtp.mailgun.org'),
                    'port' => $getConfig('mail_port', 'MAIL_PORT', 587),
                    'encryption' => $getConfig('mail_encryption', 'MAIL_ENCRYPTION', 'tls'),
                    'username' => $getConfig('mail_username', 'MAIL_USERNAME'),
                    'password' => $getConfig('mail_password', 'MAIL_PASSWORD'),
                    'timeout' => null,
                    'local_domain' => env('MAIL_EHLO_DOMAIN'),
                ],
                'ses' => [
                    'transport' => 'ses',
                ],
                'postmark' => [
                    'transport' => 'postmark',
                ],
                'mailgun' => [
                    'transport' => 'mailgun',
                ],
                'sendmail' => [
                    'transport' => 'sendmail',
                    'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
                ],
                'log' => [
                    'transport' => 'log',
                    'channel' => env('MAIL_LOG_CHANNEL'),
                ],
                'array' => [
                    'transport' => 'array',
                ],
                'failover' => [
                    'transport' => 'failover',
                    'mailers' => [
                        'smtp',
                        'log',
                    ],
                ],
                'roundrobin' => [
                    'transport' => 'roundrobin',
                    'mailers' => [
                        'ses',
                        'postmark',
                    ],
                ],
            ];

            return [
                'default' => $mailer,
                'mailers' => $mailers,
                'from' => [
                    'address' => $fromAddress,
                    'name' => $fromName,
                ],
                'markdown' => [
                    'theme' => 'default',
                    'paths' => [
                        resource_path('views/vendor/mail'),
                    ],
                ],
            ];
        });
    }

    /**
     * Update email settings
     */
    public function updateEmailSettings(array $settings)
    {
        $results = [];
        
        // Mailer and from settings
        if (isset($settings['mailer'])) {
            $results['mail_mailer'] = $this->setOption('mail_mailer', $settings['mailer'], 'string');
        }
        if (isset($settings['mail_from_name'])) {
            $results['mail_from_name'] = $this->setOption('mail_from_name', $settings['mail_from_name'], 'string');
        }
        if (isset($settings['mail_from_address'])) {
            $results['mail_from_address'] = $this->setOption('mail_from_address', $settings['mail_from_address'], 'string');
        }
        
        // SMTP settings
        if (isset($settings['smtp'])) {
            foreach ($settings['smtp'] as $key => $value) {
                $optionKey = 'mail_' . $key;
                $results[$optionKey] = $this->setOption($optionKey, $value, 'string');
            }
        }
        
        // Mailgun settings
        if (isset($settings['mailgun'])) {
            foreach ($settings['mailgun'] as $key => $value) {
                $optionKey = 'mailgun_' . $key;
                $results[$optionKey] = $this->setOption($optionKey, $value, 'string');
            }
        }
        
        // Postmark settings
        if (isset($settings['postmark'])) {
            foreach ($settings['postmark'] as $key => $value) {
                $optionKey = 'postmark_' . $key;
                $results[$optionKey] = $this->setOption($optionKey, $value, 'string');
            }
        }
        
        // SES settings
        if (isset($settings['ses'])) {
            foreach ($settings['ses'] as $key => $value) {
                $optionKey = 'ses_' . $key;
                $results[$optionKey] = $this->setOption($optionKey, $value, 'string');
            }
        }
        
        // Microsoft Graph settings
        if (isset($settings['microsoft'])) {
            foreach ($settings['microsoft'] as $key => $value) {
                $optionKey = 'microsoft_' . $key;
                $results[$optionKey] = $this->setOption($optionKey, $value, 'string');
            }
        }
        
        // Clear all option caches
        Cache::forget('options.all');
        Cache::forget('mail.config'); // Clear mail config cache when settings are updated
        foreach (array_keys($results) as $key) {
            Cache::forget("option.{$key}");
        }
        
        return $results;
    }

    /**
     * Get security settings (2FA and Session settings)
     */
    public function getSecuritySettings()
    {
        $settings = [
            'two_factor' => [
                'enabled' => $this->getOption('two_factor_enabled', false),
                'required' => $this->getOption('two_factor_required', false),
                'backup_codes_count' => $this->getOption('two_factor_backup_codes_count', 10),
            ],
            'session' => [
                'session_enabled' => $this->getOption('session_enabled', true),
                'session_timeout' => $this->getOption('session_timeout', 30),
                'max_login_attempts' => $this->getOption('max_login_attempts', 5),
                'lockout_duration' => $this->getOption('lockout_duration', 15),
            ],
        ];

        return $settings;
    }

    /**
     * Update security settings (2FA and Session settings)
     */
    public function updateSecuritySettings(array $settings)
    {
        $results = [];
        
        // Two Factor settings - map to database keys
        if (isset($settings['two_factor'])) {
            $keyMapping = [
                'enabled' => 'two_factor_enabled',
                'required' => 'two_factor_required',
                'backup_codes_count' => 'two_factor_backup_codes_count',
            ];
            
            foreach ($settings['two_factor'] as $key => $value) {
                $dbKey = $keyMapping[$key] ?? "two_factor_{$key}";
                $type = $key === 'backup_codes_count' ? 'integer' : 'boolean';
                $results[$dbKey] = $this->setOption($dbKey, $value, $type);
            }
        }
        
        // Session settings - keys are already correct
        if (isset($settings['session'])) {
            foreach ($settings['session'] as $key => $value) {
                $type = $key === 'session_enabled' ? 'boolean' : 'integer';
                $results[$key] = $this->setOption($key, $value, $type);
            }
        }
        
        // Clear all option caches to ensure fresh data
        Cache::forget('options.all');
        foreach (array_keys($results) as $key) {
            Cache::forget("option.{$key}");
        }
        
        return $results;
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings(array $settings)
    {
        $results = [];
        
        foreach ($settings as $category => $options) {
            foreach ($options as $key => $value) {
                $optionKey = "{$category}_{$key}";
                $results[$optionKey] = $this->setOption($optionKey, $value, $this->getOptionType($value));
            }
        }
        
        return $results;
    }

    /**
     * Determine option type based on value
     */
    private function getOptionType($value)
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_int($value)) {
            return 'integer';
        } elseif (is_float($value)) {
            return 'float';
        } elseif (is_array($value) || is_object($value)) {
            return 'json';
        }
        
        return 'string';
    }

    /**
     * Initialize default options
     */
    public function initializeDefaultOptions()
    {
        $defaultOptions = [
            // Two-Factor Authentication
            'two_factor_enabled' => ['value' => false, 'type' => 'boolean', 'description' => 'Enable two-factor authentication'],
            'two_factor_required' => ['value' => false, 'type' => 'boolean', 'description' => 'Require two-factor authentication for all users'],
            'two_factor_backup_codes_count' => ['value' => 10, 'type' => 'integer', 'description' => 'Number of backup codes to generate'],
            
            // Security Settings
            'session_enabled' => ['value' => true, 'type' => 'boolean', 'description' => 'Enable session timeout'],
            'session_timeout' => ['value' => 30, 'type' => 'integer', 'description' => 'Session timeout in minutes'],
            'max_login_attempts' => ['value' => 5, 'type' => 'integer', 'description' => 'Maximum login attempts before lockout'],
            'lockout_duration' => ['value' => 15, 'type' => 'integer', 'description' => 'Account lockout duration in minutes'],
            'password_min_length' => ['value' => 8, 'type' => 'integer', 'description' => 'Minimum password length'],
            
            // General Settings
            'site_name' => ['value' => 'CorePanel', 'type' => 'string', 'description' => 'Site name'],
            'site_description' => ['value' => 'Admin Panel', 'type' => 'string', 'description' => 'Site description'],
            'timezone' => ['value' => 'Asia/Manila', 'type' => 'string', 'description' => 'Default timezone'],
        ];

        foreach ($defaultOptions as $key => $data) {
            if (!Option::where('option_key', $key)->exists()) {
                Option::set($key, $data['value'], $data['type'], $data['description']);
            }
        }
    }
}
