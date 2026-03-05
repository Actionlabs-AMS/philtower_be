<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Microsoft Graph Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Microsoft Graph API integration
    |
    */

    'tenant_id' => env('MICROSOFT_TENANT_ID'),
    'client_id' => env('MICROSOFT_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    'sender_email' => env('MICROSOFT_SENDER_EMAIL'),
    
    /*
    |--------------------------------------------------------------------------
    | Microsoft Graph API Settings
    |--------------------------------------------------------------------------
    */
    
    'api_version' => 'v1.0',
    'scope' => 'https://graph.microsoft.com/.default',
    'token_url' => 'https://login.microsoftonline.com/{tenant_id}/oauth2/v2.0/token',
    'send_mail_url' => 'https://graph.microsoft.com/v1.0/users/{sender_email}/sendMail',
    
    /*
    |--------------------------------------------------------------------------
    | SSL Certificate Settings
    |--------------------------------------------------------------------------
    */
    
    'verify_ssl' => env('MICROSOFT_VERIFY_SSL', true),
    'cert_path' => storage_path('certs/cacert.pem'),
];
