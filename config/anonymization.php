<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Data Anonymization Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for data anonymization
    | to ensure GDPR compliance and data privacy.
    |
    */

    'enabled' => env('ANONYMIZATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Anonymization Methods
    |--------------------------------------------------------------------------
    |
    | Different methods for anonymizing data based on field type.
    |
    */

    'methods' => [
        'email' => 'hash', // hash, mask, replace
        'phone' => 'mask', // hash, mask, replace
        'name' => 'replace', // hash, mask, replace
        'address' => 'replace', // hash, mask, replace
        'ssn' => 'hash', // hash, mask, replace
        'credit_card' => 'mask', // hash, mask, replace
        'date_of_birth' => 'replace', // hash, mask, replace
        'ip_address' => 'hash', // hash, mask, replace
    ],

    /*
    |--------------------------------------------------------------------------
    | Anonymization Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns for different anonymization methods.
    |
    */

    'patterns' => [
        'email' => [
            'mask' => '***@***.***',
            'replace' => 'anonymized@example.com',
        ],
        'phone' => [
            'mask' => '***-***-****',
            'replace' => '000-000-0000',
        ],
        'name' => [
            'mask' => '*** ***',
            'replace' => 'Anonymous User',
        ],
        'address' => [
            'mask' => '*** *** St, ***, ** *****',
            'replace' => '123 Anonymous Street, City, ST 12345',
        ],
        'ssn' => [
            'mask' => '***-**-****',
            'replace' => '000-00-0000',
        ],
        'credit_card' => [
            'mask' => '****-****-****-****',
            'replace' => '0000-0000-0000-0000',
        ],
        'ip_address' => [
            'mask' => '***.***.***.***',
            'replace' => '0.0.0.0',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model-Specific Anonymization
    |--------------------------------------------------------------------------
    |
    | Define which fields should be anonymized for specific models.
    | Format: 'ModelName' => ['field1' => 'method', 'field2' => 'method', ...]
    |
    */

    'model_anonymization' => [
        'User' => [
            'email' => 'hash',
            'phone' => 'mask',
            'first_name' => 'replace',
            'last_name' => 'replace',
            'address' => 'replace',
            'date_of_birth' => 'replace',
        ],
        'Profile' => [
            'bio' => 'replace',
            'personal_notes' => 'replace',
            'emergency_contact' => 'mask',
        ],
        'Payment' => [
            'credit_card_number' => 'mask',
            'bank_account_number' => 'mask',
            'routing_number' => 'mask',
        ],
        'AuditLog' => [
            'ip_address' => 'hash',
            'user_agent' => 'replace',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | GDPR Compliance Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to GDPR compliance requirements.
    |
    */

    'gdpr' => [
        'retention_period_days' => env('GDPR_RETENTION_DAYS', 2555), // 7 years
        'anonymization_trigger' => env('GDPR_ANONYMIZATION_TRIGGER', 'deletion'), // deletion, request, automatic
        'log_anonymization' => env('GDPR_LOG_ANONYMIZATION', true),
        'anonymization_reason' => env('GDPR_ANONYMIZATION_REASON', 'GDPR compliance'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anonymization Triggers
    |--------------------------------------------------------------------------
    |
    | When to trigger anonymization.
    |
    */

    'triggers' => [
        'on_delete' => env('ANONYMIZE_ON_DELETE', true),
        'on_soft_delete' => env('ANONYMIZE_ON_SOFT_DELETE', false),
        'on_user_request' => env('ANONYMIZE_ON_USER_REQUEST', true),
        'automatic_after_days' => env('ANONYMIZE_AUTOMATIC_AFTER_DAYS', 2555), // 7 years
    ],

    /*
    |--------------------------------------------------------------------------
    | Hash Settings
    |--------------------------------------------------------------------------
    |
    | Settings for hash-based anonymization.
    |
    */

    'hash' => [
        'algorithm' => env('ANONYMIZATION_HASH_ALGORITHM', 'sha256'),
        'salt' => env('ANONYMIZATION_HASH_SALT', config('app.key')),
        'prefix' => env('ANONYMIZATION_HASH_PREFIX', 'anon:'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log anonymization activities for audit purposes.
    |
    */

    'logging' => [
        'enabled' => env('ANONYMIZATION_LOGGING_ENABLED', true),
        'log_level' => env('ANONYMIZATION_LOG_LEVEL', 'info'),
        'log_channel' => env('ANONYMIZATION_LOG_CHANNEL', 'single'),
    ],
];
