<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => env('PUBLIC_FILESYSTEM_DRIVER', 'local'),
            
            // FIXED: Only apply the local physical storage path prefix if the local driver is active.
            // On S3/R2 drivers, this is set to null to prevent absolute Windows paths (C:\...) from being sent to Supabase.
            'root' => env('PUBLIC_FILESYSTEM_DRIVER', 'local') === 'local' ? storage_path('app/public') : null,
            
            'url' => env('PUBLIC_FILESYSTEM_URL', rtrim(env('APP_URL', 'http://localhost'), '/').'/storage'),
            
            // FIXED: Set to 'private' (or omit) so Laravel does NOT send 'x-amz-acl' headers.
            // Supabase's S3 API does not support S3 ACLs and will reject requests containing them.
            'visibility' => 'private', 
            
            // FIXED: Set to true so any credentials, bucket name, or connection errors throw immediately
            'throw' => true, 
            
            'report' => false,

            // Supabase S3 credentials (automatically loaded from .env)
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'auto'),
            'bucket' => env('AWS_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];