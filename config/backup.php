<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Scheduling Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how backup schedules are executed. Options:
    | - 'auto': Automatically detect best method
    | - 'on_request': Check schedules on API requests (works on any hosting)
    | - 'queue': Use Laravel delayed jobs (requires queue worker)
    | - 'webhook': Use external webhook services
    | - 'manual': Only manual triggers
    |
    */
    'scheduling' => [
        'method' => env('BACKUP_SCHEDULING_METHOD', 'auto'),
        'webhook_token' => env('BACKUP_WEBHOOK_TOKEN'),
        'check_cooldown' => env('BACKUP_SCHEDULE_CHECK_COOLDOWN', 5), // minutes
        'middleware_routes' => [
            '/api/dashboard/*',
            '/api/user-management/*',
            '/api/system-settings/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Backup Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'compression' => 'gzip',
        'encrypted' => false,
        'storage_disk' => 'local',
        'retention_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'local_path' => 'backups',
        's3_path' => 'backups',
    ],
];

