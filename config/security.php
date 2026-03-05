<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration settings for the
    | PathCast application. These settings help protect against common
    | security vulnerabilities and attacks.
    |
    */

    'enabled' => env('SECURITY_ENABLED', true),

    'headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),
        'hsts_enabled' => env('SECURITY_HSTS_ENABLED', true),
    ],

    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),
        'api_limit' => env('API_RATE_LIMIT', 60), // requests per minute
        'auth_limit' => env('AUTH_RATE_LIMIT', 5), // requests per minute
        'login_limit' => env('LOGIN_RATE_LIMIT', 3), // requests per minute
    ],

    'password_policy' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
        'max_age_days' => env('PASSWORD_MAX_AGE_DAYS', 90),
    ],

    'session' => [
        'lifetime' => env('SESSION_LIFETIME', 120), // minutes
        'secure' => env('SESSION_SECURE', false),
        'http_only' => env('SESSION_HTTP_ONLY', true),
        'same_site' => env('SESSION_SAME_SITE', 'lax'),
    ],

    'cors' => [
        'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),
        'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),
        'allowed_headers' => explode(',', env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With')),
        'max_age' => env('CORS_MAX_AGE', 86400),
    ],

    'input_validation' => [
        'max_string_length' => env('MAX_STRING_LENGTH', 255),
        'max_file_size' => env('MAX_FILE_SIZE', 10240), // KB
        'allowed_file_types' => explode(',', env('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf,doc,docx')),
        'sanitize_input' => env('SANITIZE_INPUT', true),
    ],

    'audit' => [
        'enabled' => env('AUDIT_ENABLED', true),
        'log_failed_attempts' => env('AUDIT_LOG_FAILED_ATTEMPTS', true),
        'log_successful_actions' => env('AUDIT_LOG_SUCCESSFUL_ACTIONS', true),
        'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
    ],

    'encryption' => [
        'enabled' => env('ENCRYPTION_ENABLED', true),
        'key_rotation_days' => env('ENCRYPTION_KEY_ROTATION_DAYS', 365),
        'algorithm' => env('ENCRYPTION_ALGORITHM', 'AES-256-CBC'),
        'key' => env('ENCRYPTION_KEY', config('app.key')),
        'prefix' => env('ENCRYPTION_PREFIX', 'encrypted:'),
    ],

    'anonymization' => [
        'enabled' => env('ANONYMIZATION_ENABLED', true),
        'gdpr_compliance' => env('GDPR_COMPLIANCE_ENABLED', true),
        'retention_days' => env('GDPR_RETENTION_DAYS', 2555), // 7 years
        'log_anonymization' => env('GDPR_LOG_ANONYMIZATION', true),
    ],

    'csrf' => [
        'enabled' => env('CSRF_ENABLED', true),
        'token_lifetime' => env('CSRF_TOKEN_LIFETIME', 120), // minutes
    ],

    'xss_protection' => [
        'enabled' => env('XSS_PROTECTION_ENABLED', true),
        'sanitize_output' => env('XSS_SANITIZE_OUTPUT', true),
    ],

    'sql_injection' => [
        'enabled' => env('SQL_INJECTION_PROTECTION_ENABLED', true),
        'use_prepared_statements' => env('SQL_USE_PREPARED_STATEMENTS', true),
    ],
];
