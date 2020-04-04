<?php

return [
    // The backup driver to use for this Panel instance. All client generated server backups
    // will be stored in this location by default. It is possible to change this once backups
    // have been made, without losing data.
    'driver' => env('APP_BACKUP_DRIVER', 'local'),

    'disks' => [
        // There is no configuration for the local disk for Wings. That configuration
        // is determined by the Daemon configuration, and not the Panel.
        'local' => [],

        // Configuration for storing backups in Amazon S3.
        's3' => [
            'region' => '',
            'access_key' => '',
            'access_secret_key' => '',

            // The S3 bucket to use for backups.
            'bucket' => '',

            // The location within the S3 bucket where backups will be stored. Backups
            // are stored within a folder using the server's UUID as the name. Each
            // backup for that server lives within that folder.
            'location' => '',
        ],
    ],
];
