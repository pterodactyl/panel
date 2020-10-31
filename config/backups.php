<?php

use Pterodactyl\Models\Backup;

return [
    // The backup driver to use for this Panel instance. All client generated server backups
    // will be stored in this location by default. It is possible to change this once backups
    // have been made, without losing data.
    'default' => env('APP_BACKUP_DRIVER', Backup::ADAPTER_WINGS),

    'disks' => [
        // There is no configuration for the local disk for Wings. That configuration
        // is determined by the Daemon configuration, and not the Panel.
        'wings' => [
            'adapter' => Backup::ADAPTER_WINGS,
        ],

        // Configuration for storing backups in Amazon S3. This uses the same credentials
        // specified in filesystems.php but does include some more specific settings for
        // backups, notably bucket, location, and use_accelerate_endpoint.
        's3' => [
            'adapter' => Backup::ADAPTER_AWS_S3,

            'region' => env('AWS_DEFAULT_REGION'),
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),

            // The S3 bucket to use for backups.
            'bucket' => env('AWS_BACKUPS_BUCKET'),

            // The location within the S3 bucket where backups will be stored. Backups
            // are stored within a folder using the server's UUID as the name. Each
            // backup for that server lives within that folder.
            'prefix' => env('AWS_BACKUPS_BUCKET') ?? '',

            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'use_accelerate_endpoint' => env('AWS_BACKUPS_USE_ACCELERATE', false),
        ],
    ],
];
