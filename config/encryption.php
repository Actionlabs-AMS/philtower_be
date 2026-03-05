<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Encryption Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for database field encryption
    | to protect sensitive data at rest.
    |
    */

    'enabled' => env('ENCRYPTION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Encryption Algorithm
    |--------------------------------------------------------------------------
    |
    | The encryption algorithm to use for encrypting sensitive fields.
    | Supported: AES-256-CBC, AES-256-CBC
    |
    */

    'algorithm' => env('ENCRYPTION_ALGORITHM', 'AES-256-CBC'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | The encryption key used for encrypting/decrypting data.
    | This should be a 32-character random string.
    |
    */

    'key' => env('ENCRYPTION_KEY', config('app.key')),

    /*
    |--------------------------------------------------------------------------
    | Default Encrypted Fields
    |--------------------------------------------------------------------------
    |
    | Default fields that should be encrypted across all models.
    | These can be overridden in individual models.
    |
    */

    'default_encrypted_fields' => [
        'email',
        'phone',
        'ssn',
        'credit_card',
        'bank_account',
        'personal_id',
        'address',
        'date_of_birth',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model-Specific Encryption
    |--------------------------------------------------------------------------
    |
    | Define which fields should be encrypted for specific models.
    | Format: 'ModelName' => ['field1', 'field2', ...]
    |
    */

    'model_encryption' => [
        'User' => [
            'email',
            'phone',
            'address',
            'date_of_birth',
        ],
        'Profile' => [
            'bio',
            'personal_notes',
            'emergency_contact',
        ],
        'Payment' => [
            'credit_card_number',
            'bank_account_number',
            'routing_number',
        ],
        'Option' => [
            // Note: Option model uses custom encryption logic based on option_key
            // Sensitive email settings are automatically encrypted:
            // - mail_password (SMTP password)
            // - mailgun_secret (Mailgun API secret)
            // - postmark_token (Postmark API token)
            // - ses_key (AWS SES access key)
            // - ses_secret (AWS SES secret key)
            // - microsoft_tenant_id (Microsoft Graph tenant ID)
            // - microsoft_client_id (Microsoft Graph client ID)
            // - microsoft_client_secret (Microsoft Graph client secret)
            // The encryption is handled in Option::shouldEncrypt() method
            'option_value', // Only encrypted when option_key is in the sensitive list
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix to identify encrypted data in the database.
    | This helps distinguish encrypted from unencrypted data.
    |
    */

    'prefix' => env('ENCRYPTION_PREFIX', 'encrypted:'),

    /*
    |--------------------------------------------------------------------------
    | Key Rotation
    |--------------------------------------------------------------------------
    |
    | Configuration for encryption key rotation.
    |
    */

    'key_rotation' => [
        'enabled' => env('ENCRYPTION_KEY_ROTATION_ENABLED', false),
        'rotation_days' => env('ENCRYPTION_KEY_ROTATION_DAYS', 365),
        'old_key_retention_days' => env('ENCRYPTION_OLD_KEY_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize encryption performance.
    |
    */

    'performance' => [
        'batch_size' => env('ENCRYPTION_BATCH_SIZE', 100),
        'cache_encrypted_fields' => env('ENCRYPTION_CACHE_FIELDS', true),
        'lazy_encryption' => env('ENCRYPTION_LAZY_MODE', false),
    ],
];
