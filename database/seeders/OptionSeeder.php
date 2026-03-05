<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Option;

class OptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultOptions = [
            // Two-Factor Authentication
            [
                'option_key' => 'two_factor_enabled',
                'option_value' => 'false',
                'option_type' => 'boolean',
                'description' => 'Enable two-factor authentication globally',
            ],
            [
                'option_key' => 'two_factor_required',
                'option_value' => 'false',
                'option_type' => 'boolean',
                'description' => 'Require two-factor authentication for all users',
            ],
            [
                'option_key' => 'two_factor_backup_codes_count',
                'option_value' => '10',
                'option_type' => 'integer',
                'description' => 'Number of backup codes to generate for each user',
            ],
            
            // Security Settings
            [
                'option_key' => 'session_timeout',
                'option_value' => '30',
                'option_type' => 'integer',
                'description' => 'Session timeout in minutes',
            ],
            [
                'option_key' => 'max_login_attempts',
                'option_value' => '5',
                'option_type' => 'integer',
                'description' => 'Maximum login attempts before account lockout',
            ],
            [
                'option_key' => 'password_min_length',
                'option_value' => '8',
                'option_type' => 'integer',
                'description' => 'Minimum password length requirement',
            ],
            [
                'option_key' => 'password_require_special_chars',
                'option_value' => 'true',
                'option_type' => 'boolean',
                'description' => 'Require special characters in passwords',
            ],
            [
                'option_key' => 'password_require_numbers',
                'option_value' => 'true',
                'option_type' => 'boolean',
                'description' => 'Require numbers in passwords',
            ],
            
            // General Settings
            [
                'option_key' => 'site_name',
                'option_value' => 'CorePanel',
                'option_type' => 'string',
                'description' => 'Application name',
            ],
            [
                'option_key' => 'site_description',
                'option_value' => 'Admin Panel Management System',
                'option_type' => 'string',
                'description' => 'Application description',
            ],
            [
                'option_key' => 'timezone',
                'option_value' => 'Asia/Manila',
                'option_type' => 'string',
                'description' => 'Default timezone',
            ],
            [
                'option_key' => 'date_format',
                'option_value' => 'Y-m-d',
                'option_type' => 'string',
                'description' => 'Default date format',
            ],
            [
                'option_key' => 'time_format',
                'option_value' => 'H:i:s',
                'option_type' => 'string',
                'description' => 'Default time format',
            ],
            
            // Email Settings
            [
                'option_key' => 'mail_from_name',
                'option_value' => 'CorePanel',
                'option_type' => 'string',
                'description' => 'Default sender name for emails',
            ],
            [
                'option_key' => 'mail_from_address',
                'option_value' => 'noreply@corepanel.com',
                'option_type' => 'string',
                'description' => 'Default sender email address',
            ],
            
            // UI Settings
            [
                'option_key' => 'items_per_page',
                'option_value' => '10',
                'option_type' => 'integer',
                'description' => 'Default number of items per page in lists',
            ],
            [
                'option_key' => 'theme_color',
                'option_value' => 'blue',
                'option_type' => 'string',
                'description' => 'Default theme color',
            ],
            [
                'option_key' => 'sidebar_collapsed',
                'option_value' => 'false',
                'option_type' => 'boolean',
                'description' => 'Default sidebar state (collapsed/expanded)',
            ],
        ];

        foreach ($defaultOptions as $option) {
            Option::updateOrCreate(
                ['option_key' => $option['option_key']],
                $option
            );
        }
    }
}