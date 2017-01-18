<?php

return [
    'index' => [
        'title' => 'Viewing Server :name',
        'header' => 'Server Console',
        'header_sub' => 'Control your server in real time.',
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
    ]
];
