<?php

use Pterodactyl\Models\Backup;

return [
    // The backup driver to use for this Panel instance. All client generated server backups
    // will be stored in this location by default. It is possible to change this once backups
    // have been made, without losing data.
    'default' => env('APP_BACKUP_DRIVER', Backup::ADAPTER_WINGS),

    // This value is used to determine the lifespan of UploadPart presigned urls that wings
    // uses to upload backups to S3 storage.  Value is in minutes, so this would default to an hour.
    'presigned_url_lifespan' => env('BACKUP_PRESIGNED_URL_LIFESPAN', 60),

    // This value defines the maximal size of a single part for the S3 multipart upload during backups
    // The maximal part size must be given in bytes. The default value is 5GB.
    // Note that 5GB is the maximum for a single part when using AWS S3.
    'max_part_size' => env('BACKUP_MAX_PART_SIZE', 5 * 1024 * 1024 * 1024),

    // The time to wait before automatically failing a backup, time is in minutes and defaults
    // to 6 hours.  To disable this feature, set the value to `0`.
    'prune_age' => env('BACKUP_PRUNE_AGE', 360),

    // Defines the backup creation throttle limits for users. In this default example, we allow
    // a user to create two (successful or pending) backups per 10 minutes. Even if they delete
    // a backup it will be included in the throttle count.
    //
    // Set the period to "0" to disable this throttle. The period is defined in seconds.
    'throttles' => [
        'limit' => env('BACKUP_THROTTLE_LIMIT', 2),
        'period' => env('BACKUP_THROTTLE_PERIOD', 600),
    ],

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

            'storage_class' => env('AWS_BACKUPS_STORAGE_CLASS'),
        ],
    ],
];
