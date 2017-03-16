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
            'base' => [
                'title' => 'Base Information',
                'information' => [
                    'title' => 'Base Information',
                    'description' => 'Returns a listing of all servers that this account has access to.',
                ],
            ],
            'user_management' => [
                'title' => 'User Management',
                'list' => [
                    'title' => 'List Users',
                    'description' => 'Allows listing of all users currently on the system.',
                ],
                'create' => [
                    'title' => 'Create User',
                    'description' => 'Allows creating a new user on the system.',
                ],
                'view' => [
                    'title' => 'List Single User',
                    'description' => 'Allows viewing details about a specific user including active services.',
                ],
                'update' => [
                    'title' => 'Update User',
                    'description' => 'Allows modifying user details (email, password, TOTP information).',
                ],
                'delete' => [
                    'title' => 'Delete User',
                    'description' => 'Allows deleting a user.',
                ],
            ],
            'node_management' => [
                'title' => 'Node Management',
                'list' => [
                    'title' => 'List Nodes',
                    'description' => 'Allows listing of all nodes currently on the system.',
                ],
                'create' => [
                    'title' => 'Create Node',
                    'description' => 'Allows creating a new node on the system.',
                ],
                'view' => [
                    'title' => 'List Single Node',
                    'description' => 'Allows viewing details about a specific node including active services.',
                ],
                'allocations' => [
                    'title' => 'List Allocations',
                    'description' => 'Allows viewing all allocations on the panel for all nodes.',
                ],
                'delete' => [
                    'title' => 'Delete Node',
                    'description' => 'Allows deleting a node.',
                ],
            ],
            'server_management' => [
                'title' => 'Server Management',
                'server' => [
                    'title' => 'Server Info',
                    'description' => 'Allows access to viewing information about a single server including current stats and allocations.',
                ],
                'power' => [
                    'title' => 'Server Power',
                    'description' => 'Allows access to control server power status.',
                ],
                'view' => [
                    'title' => 'Show Single Server',
                    'description' => 'Allows viewing details about a specific server including the daemon_token as well as current process information.',
                ],
                'list' => [
                    'title' => 'List Servers',
                    'description' => 'Allows listing of all servers currently on the system.',
                ],
                'create' => [
                    'title' => 'Create Server',
                    'description' => 'Allows creating a new server on the system.',
                ],
                'config' => [
                    'title' => 'Update Configuration',
                    'description' => 'Allows modifying server config (name, owner, and access token).',
                ],
                'build' => [
                    'title' => 'Update Build',
                    'description' => 'Allows modifying a server\'s build parameters such as memory, CPU, and disk space along with assigned and default IPs.',
                ],
                'suspend' => [
                    'title' => 'Suspend Server',
                    'description' => 'Allows suspending a server instance.',
                ],
                'unsuspend' => [
                    'title' => 'Unsuspend Server',
                    'description' => 'Allows unsuspending a server instance.',
                ],
                'delete' => [
                    'title' => 'Delete Server',
                    'description' => 'Allows deleting a server.',
                ],
            ],
            'service_management' => [
                'title' => 'Service Management',
                'list' => [
                    'title' => 'List Services',
                    'description' => 'Allows listing of all services configured on the system.',
                ],
                'view' => [
                    'title' => 'List Single Service',
                    'description' => 'Allows listing details about each service on the system including service options and variables.',
                ],
            ],
            'location_management' => [
                'title' => 'Location Management',
                'list' => [
                    'title' => 'List Locations',
                    'description' => 'Allows listing all locations and thier associated nodes.',
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
