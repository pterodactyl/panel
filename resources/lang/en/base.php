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
            'desc' => 'The requested server is still completing the install process. Please check back in a few minutes, you should recieve an email as soon as this process is completed.',
        ],
        'suspended' => [
            'header' => 'Server Suspended',
            'desc' => 'This server has been suspended and cannot be accessed.',
        ],
    ],
    'index' => [
        'header' => 'Your Servers',
        'header_sub' => 'Servers you own and have access to.',
        'list' => 'Server List',
    ],
    'api' => [
        'index' => [
            'header' => 'API Access',
            'header_sub' => 'Manage your API access keys.',
            'list' => 'API Keys',
            'create_new' => 'Create New API key',
        ],
        'new' => [
            'header' => 'New API Key',
            'header_sub' => 'Create a new API access key',
            'form_title' => 'Details',
            'descriptive_memo' => [
                'title' => 'Descriptive Memo',
                'description' => 'Enter a brief description of what this API key will be used for.',
            ],
            'allowed_ips' => [
                'title' => 'Allowed IPs',
                'description' => 'Enter a line delimitated list of IPs that are allowed to access the API using this key. CIDR notation is allowed. Leave blank to allow any IP.',
            ],
        ],
        'permissions' => [
            'user' => [
                'server_header' => 'User Server Permissions',
                'server' => [
                    'list' => [
                        'title' => 'List Servers',
                        'desc' => 'Allows listing of all servers a user owns or has access to as a subuser.',
                    ],
                    'view' => [
                        'title' => 'View Server',
                        'desc'=> 'Allows viewing of specific server user can access.',
                    ],
                    'power' => [
                        'title' => 'Toggle Power',
                        'desc'=> 'Allow toggling of power status for a server.',
                    ],
                    'command' => [
                        'title' => 'Send Command',
                        'desc'=> 'Allow sending of a command to a running server.',
                    ],
                ],
            ],
            'admin' => [
                'server_header' => 'Server Control',
                'server' => [
                    'list' => [
                        'title' => 'List Servers',
                        'desc' => 'Allows listing of all servers currently on the system.',
                    ],
                    'view' => [
                        'title' => 'View Server',
                        'desc' => 'Allows view of single server including service and details.',
                    ],
                    'delete' => [
                        'title' => 'Delete Server',
                        'desc' => 'Allows deletion of a server from the system.',
                    ],
                    'create' => [
                        'title' => 'Create Server',
                        'desc' => 'Allows creation of a new server on the system.',
                    ],
                    'edit-details' => [
                        'title' => 'Edit Server Details',
                        'desc' => 'Allows editing of server details such as name, owner, description, and secret key.',
                    ],
                    'edit-container' => [
                        'title' => 'Edit Server Container',
                        'desc' => 'Allows for modification of the docker container the server runs in.',
                    ],
                    'suspend' => [
                        'title' => 'Suspend Server',
                        'desc' => 'Allows for the suspension and unsuspension of a given server.',
                    ],
                    'install' => [
                        'title' => 'Toggle Install Status',
                        'desc' => '',
                    ],
                    'rebuild' => [
                        'title' => 'Rebuild Server',
                        'desc' => '',
                    ],
                    'edit-build' => [
                        'title' => 'Edit Server Build',
                        'desc' => 'Allows editing of server build setting such as CPU and memory allocations.',
                    ],
                    'edit-startup' => [
                        'title' => 'Edit Server Startup',
                        'desc' => 'Allows modification of server startup commands and parameters.',
                    ],
                ],
                'location_header' => 'Location Control',
                'location' => [
                    'list' => [
                        'title' => 'List Locations',
                        'desc' => 'Allows listing all locations and thier associated nodes.',
                    ],
                ],
                'node_header' => 'Node Control',
                'node' => [
                    'list' => [
                        'title' => 'List Nodes',
                        'desc' => 'Allows listing of all nodes currently on the system.',
                    ],
                    'view' => [
                        'title' => 'View Node',
                        'desc' => 'llows viewing details about a specific node including active services.',
                    ],
                    'view-config' => [
                        'title' => 'View Node Configuration',
                        'desc' => 'Danger. This allows the viewing of the node configuration file used by the daemon, and exposes secret daemon tokens.',
                    ],
                    'create' => [
                        'title' => 'Create Node',
                        'desc' => 'Allows creating a new node on the system.',
                    ],
                    'delete' => [
                        'title' => 'Delete Node',
                        'desc' => 'Allows deletion of a node from the system.',
                    ],
                ],
                'user_header' => 'User Control',
                'user' => [
                    'list' => [
                        'title' => 'List Users',
                        'desc' => 'Allows listing of all users currently on the system.',
                    ],
                    'view' => [
                        'title' => 'View User',
                        'desc' => 'Allows viewing details about a specific user including active services.',
                    ],
                    'create' => [
                        'title' => 'Create User',
                        'desc' => 'Allows creating a new user on the system.',
                    ],
                    'edit' => [
                        'title' => 'Update User',
                        'desc' => 'Allows modification of user details.',
                    ],
                    'delete' => [
                        'title' => 'Delete User',
                        'desc' => 'Allows deleting a user.',
                    ],
                ],
                'service_header' => 'Service Control',
                'service' => [
                    'list' => [
                        'title' => 'List Services',
                        'desc' => 'Allows listing of all services configured on the system.',
                    ],
                    'view' => [
                        'title' => 'View Service',
                        'desc' => 'Allows listing details about each service on the system including service options and variables.',
                    ],
                ],
                'option_header' => 'Option Control',
                'option' => [
                    'list' => [
                        'title' => 'List Options',
                        'desc' => '',
                    ],
                    'view' => [
                        'title' => 'View Option',
                        'desc' => '',
                    ],
                ],
                'pack_header' => 'Pack Control',
                'pack' => [
                    'list' => [
                        'title' => 'List Packs',
                        'desc' => '',
                    ],
                    'view' => [
                        'title' => 'View Pack',
                        'desc' => '',
                    ],
                ],
            ],
        ],
    ],
    'account' => [
        'header' => 'Your Account',
        'header_sub' => 'Manage your account details.',
        'update_pass' => 'Update Password',
        'update_email' => 'Update Email Address',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'new_password_again' => 'Repeat New Password',
        'new_email' => 'New Email Address',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'update_identitity' => 'Update Identity',
        'username_help' => 'Your username must be unique to your account, and may only contain the following characters: :requirements.',
        'invalid_pass' => 'The password provided was not valid for this account.',
        'exception' => 'An error occurred while attempting to update your account.',
    ],
    'security' => [
        'header' => 'Account Security',
        'header_sub' => 'Control active sessions and 2-Factor Authentication.',
        'sessions' => 'Active Sessions',
        '2fa_header' => '2-Factor Authentication',
        '2fa_token_help' => 'Enter the 2FA Token generated by your app (Google Authenticatior, Authy, etc.).',
        'disable_2fa' => 'Disable 2-Factor Authentication',
        '2fa_enabled' => '2-Factor Authentication is enabled on this account and will be required in order to login to the panel. If you would like to disable 2FA, simply enter a valid token below and submit the form.',
        '2fa_disabled' => '2-Factor Authentication is disabled on your account! You should enable 2FA in order to add an extra level of protection on your account.',
        'enable_2fa' => 'Enable 2-Factor Authentication',
        '2fa_qr' => 'Confgure 2FA on Your Device',
        '2fa_checkpoint_help' => 'Use the 2FA application on your phone to take a picture of the QR code to the left, or manually enter the code under it. Once you have done so, generate a token and enter it below.',
    ],
];
