<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS, Brevo, PhilSMS, and more. This file provides
    | the de facto location for this type of information, allowing packages
    | and custom dispatchers to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PhilSMS REST API Gateway
    |--------------------------------------------------------------------------
    |
    | Gateway configuration for dispatching One-Time Password (OTP) verification
    | codes and clinical notifications to Philippine mobile numbers.
    |
    */
    'philsms' => [
        'token' => env('PHILSMS_API_TOKEN', env('SMS_API_KEY')),
        'endpoint' => env('PHILSMS_ENDPOINT', env('SMS_ENDPOINT', 'https://dashboard.philsms.com/api/v3/sms/send')),
        'sender_id' => env('PHILSMS_SENDER_ID', env('SMS_SENDER_NAME', 'PhilSMS')),
    ],

];