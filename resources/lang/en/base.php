<?php

return [
    'validation_error' => 'There was an error with one or more fields in the request.',
    'errors' => [
        'return' => 'Return to Previous Page',
        'home' => 'Go Home',
        '403' => [
            'header' => 'Forbidden',
            'desc' => 'You do not have permission to access this resource on this server.',
        ],
        '404' => [
            'header' => 'File Not Found',
            'desc' => 'We were unable to locate the requested resource on the server.',
        ],
        'installing' => [
            'header' => 'Server Installing',
            'desc' => 'The requested server is still completing the install process. Please check back in a few minutes, you should receive an email as soon as this process is completed.',
        ],
        'suspended' => [
            'header' => 'Server Suspended',
            'desc' => 'This server has been suspended and cannot be accessed.',
        ],
        'maintenance' => [
            'header' => 'Node Under Maintenance',
            'title' => 'Temporarily Unavailable',
            'desc' => 'This node is under maintenance, therefore your server can temporarily not be accessed.',
        ],
    ],
    'index' => [
        'header' => 'Your Servers',
        'header_sub' => 'Servers you have access to.',
        'list' => 'Server List',
    ],
    'api' => [
        'index' => [
            'list' => 'Your Keys',
            'header' => 'Account API',
            'header_sub' => 'Manage access keys that allow you to perform actions against the panel.',
            'create_new' => 'Create New API key',
            'keypair_created' => 'An API key has been successfully generated and is listed below.',
        ],
        'new' => [
            'header' => 'New API Key',
            'header_sub' => 'Create a new account access key.',
            'form_title' => 'Details',
            'descriptive_memo' => [
                'title' => 'Description',
                'description' => 'Enter a brief description of this key that will be useful for reference.',
            ],
            'allowed_ips' => [
                'title' => 'Allowed IPs',
                'description' => 'Enter a line delimited list of IPs that are allowed to access the API using this key. CIDR notation is allowed. Leave blank to allow any IP.',
            ],
        ],
    ],
];
