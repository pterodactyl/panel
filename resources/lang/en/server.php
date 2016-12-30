<?php

return [
    'ajax' => [
        'socket_error' => 'We were unable to connect to the main Socket.IO server, there may be network issues currently. The panel may not work as expected.',
        'socket_status' => "This server's power status has changed to",
        'socket_status_crashed' => 'This server has been detected as CRASHED.',
    ],
    'files' => [
        'back' => 'Back to File Manager',
        'loading' => 'Loading file listing, this might take a few seconds...',
        'saved' => 'File has successfully been saved.',
        'yaml_notice' => "You are currently editing a YAML file. These files do not accept tabs, they must use spaces. We've gone ahead and made it so that hitting tab will insert :dropdown spaces.",
    ],
    'index' => [
        'add_new' => 'Add New Server',
        'allocation' => 'Allocation',
        'command' => 'Enter Console Command',
        'connection' => 'Default Connection',
        'control' => 'Control Server',
        'cpu_use' => 'CPU Usage',
        'disk_space' => 'Disk Space',
        'memory_use' => 'Memory Usage',
        'mem_limit' => 'Memory Limit',
        'server_info' => 'Server Information',
        'usage' => 'Usage',
        'xaxis' => 'Time (2s Increments)',
    ],
];
