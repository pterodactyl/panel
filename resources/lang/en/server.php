<?php

return [
    'index' => [
        'title' => 'Viewing Server :name',
        'header' => 'Server Console',
        'header_sub' => 'Control your server in real time.',
    ],
    'files' => [
        'header' => 'File Manager',
        'header_sub' => 'Manage all of your files directly from the web.',
        'loading' => 'Loading initial file structure, this could take a few seconds.',
        'path' => 'When configuring any file paths in your server plugins or settings you should use :path as your base path. The maximum size for web-based file uploads to this node is :size.',
        'seconds_ago' => 'seconds ago',
        'file_name' => 'File Name',
        'size' => 'Size',
        'last_modified' => 'Last Modified',
        'add_new' => 'Add New File',
        'edit' => [
            'header' => 'Edit File',
            'header_sub' => 'Make modifications to a file from the web.',
            'save' => 'Save File',
            'return' => 'Return to File Manager',
        ],
    ],
    'config' => [
        'startup' => [
            'header' => 'Start Configuration',
            'header_sub' => 'Control server startup arguments.',
            'command' => 'Startup Command',
            'edit_params' => 'Edit Parameters',
            'update' => 'Update Startup Parameters',
        ],
        'sftp' => [
            'header' => 'SFTP Configuration',
            'header_sub' => 'Account details for SFTP connections.',
            'change_pass' => 'Change SFTP Password',
            'details' => 'SFTP Details',
            'conn_addr' => 'Connection Address',
            'warning' => 'Ensure that your client is set to use SFTP and not FTP or FTPS for connections, there is a difference between the protocols.',
        ],
        'database' => [
            'header' => 'Databases',
            'header_sub' => 'All databases available for this server.',
            'your_dbs' => 'Your Databases',
            'host' => 'MySQL Host',
            'reset_password' => 'Reset Password',
            'no_dbs' => 'There are no databases listed for this server.',
            'add_db' => 'Add a new database.',
        ],
        'allocation' => [
            'header' => 'Server Allocations',
            'header_sub' => 'Control the IPs and ports available on this server.',
            'available' => 'Available Allocations',
            'help' => 'Allocation Help',
            'help_text' => 'The list to the left includes all available IPs and ports that are open for your server to use for incoming connections.'
        ],
    ],
];
