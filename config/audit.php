<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Trail Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the audit trail system.
    | You can customize the logging behavior and settings here.
    |
    */

    'enabled' => env('AUDIT_TRAIL_ENABLED', true),

    'log_channel' => env('AUDIT_LOG_CHANNEL', 'audit'),

    'log_queries' => env('AUDIT_LOG_QUERIES', false),

    'log_level' => env('AUDIT_LOG_LEVEL', 'info'),

    'max_log_size' => env('AUDIT_MAX_LOG_SIZE', 10485760), // 10MB

    'retention_days' => env('AUDIT_RETENTION_DAYS', 90),

    'sensitive_fields' => [
        'password',
        'password_confirmation',
        'user_pass',
        'user_salt',
        'user_activation_key',
        'remember_token',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'key',
        'token',
    ],

    'skip_routes' => [
        'api/files/url',
        'api/options/*',
        'api/dashboard/stats',
    ],

    'skip_methods' => [
        'OPTIONS',
    ],

    'modules' => [
        'AUTHENTICATION' => 'Authentication',
        'USER_MANAGEMENT' => 'User Management',
        'CONTENT_MANAGEMENT' => 'Content Management',
        'SITE_MANAGEMENT' => 'Site Management',
        'SYSTEM_SETTINGS' => 'System Settings',
        'DASHBOARD' => 'Dashboard',
        'FILE_MANAGEMENT' => 'File Management',
        'OPTIONS' => 'Options',
    ],

    'actions' => [
        'CREATE' => 'Create',
        'READ' => 'Read',
        'UPDATE' => 'Update',
        'DELETE' => 'Delete',
        'VIEW' => 'View',
        'LOGIN' => 'Login',
        'LOGOUT' => 'Logout',
        'REGISTER' => 'Register',
        'ACTIVATE' => 'Activate',
        'PASSWORD_RESET' => 'Password Reset',
        'BULK_DELETE' => 'Bulk Delete',
        'BULK_RESTORE' => 'Bulk Restore',
        'BULK_UPDATE' => 'Bulk Update',
        'IMPORT' => 'Import',
        'EXPORT' => 'Export',
        'VALIDATE' => 'Validate',
        'CONFIRM' => 'Confirm',
        'DECLINE' => 'Decline',
    ],
];
