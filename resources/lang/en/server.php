<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pterodactyl Language Strings for /server/{server} Routes
    |--------------------------------------------------------------------------
    */

    'ajax' => [
        'socket_error' => 'We were unable to connect to the main Socket.IO server, there may be network issues currently. <br /><br />If this is your first time seeing this message it may be because you need to accept this server\'s SSL certificate. Please click this notification and accept the certificate.',
        'socket_status' => 'This server\'s power status has changed to',
        'socket_status_crashed' => 'This server has been detected as CRASHED.',
    ],
    'index' => [
        'memory_use' => 'Memory Usage',
        'cpu_use' => 'CPU Usage',
        'xaxis' => 'Time (2s Increments)',
        'server_info' => 'Server Information',
        'connection' => 'Default Connection',
        'mem_limit' => 'Memory Limit',
        'disk_space' => 'Disk Space',
        'control' => 'Control Server',
        'info_use' => 'Information & Usage',
        'command' => 'Enter Console Command',
        'response_wait' => 'Waiting for response from server...',
        'players_null' => 'No players are online.',
    ],
    'files' => [
            'loading' => 'Loading file listing, this might take a few seconds...',
            'yaml_notice' => 'You are currently editing a YAML file. These files do not accept tabs, they must use spaces. We\'ve gone ahead and made it so that hitting tab will insert :dropdown spaces.',
            'back' => 'Back to File Manager',
            'saved' => 'File has successfully been saved.',
    ],

];
