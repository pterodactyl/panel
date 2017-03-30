<?php

return [
    'index' => [
        'title' => 'Viewing Server :name',
        'header' => 'Server Console',
        'header_sub' => 'Control your server in real time.',
    ],
    'tasks' => [
        'header' => 'Scheduled Tasks',
        'header_sub' => 'Automate your server.',
        'current' => 'Current Scheduled Tasks',
        'actions' => [
            'command' => 'Send Command',
            'power' => 'Send Power Toggle',
        ],
        'new_task' => 'Add New Task',
        'toggle' => 'Toggle Status',
        'new' => [
            'header' => 'New Task',
            'header_sub' => 'Create a new scheduled task for this server.',
            'day_of_week' => 'Day of Week',
            'custom' => 'Custom Value',
            'day_of_month' => 'Day of Month',
            'hour' => 'Hour',
            'minute' => 'Minute',
            'sun' => 'Sunday',
            'mon' => 'Monday',
            'tues' => 'Tuesday',
            'wed' => 'Wednesday',
            'thurs' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
            'submit' => 'Create Task',
            'type' => 'Task Type',
            'payload' => 'Task Payload',
            'payload_help' => 'For example, if you selected <code>Send Command</code> enter the command here. If you selected <code>Send Power Option</code> put the power action here (e.g. <code>restart</code>).',
        ],
    ],
    'users' => [
        'header' => 'Manage Users',
        'header_sub' => 'Control who can access your server.',
        'configure' => 'Configure Permissions',
        'list' => 'Accounts with Access',
        'add' => 'Add New Subuser',
        'update' => 'Update Subuser',
        'edit' => [
            'header' => 'Edit Subuser',
            'header_sub' => 'Modify user\'s access to server.',
        ],
        'new' => [
            'header' => 'Add New User',
            'header_sub' => 'Add a new user with permissions to this server.',
            'email' => 'Email Address',
            'email_help' => 'Enter the email address for the user you wish to invite to manage this server.',
            'power_header' => 'Power Management',
            'file_header' => 'File Management',
            'subuser_header' => 'Subuser Management',
            'server_header' => 'Server Management',
            'task_header' => 'Task Management',
            'sftp_header' => 'SFTP Management',
            'database_header' => 'Database Management',
            'power_start' => [
                'title' => 'Start Server',
                'description' => 'Allows user to start the server.',
            ],
            'power_stop' => [
                'title' => 'Stop Server',
                'description' => 'Allows user to stop the server.',
            ],
            'power_restart' => [
                'title' => 'Restart Server',
                'description' => 'Allows user to restart the server.',
            ],
            'power_kill' => [
                'title' => 'Kill Server',
                'description' => 'Allows user to kill the server process.',
            ],
            'send_command' => [
                'title' => 'Send Console Command',
                'description' => 'Allows sending a command from the console. If the user does not have stop or restart permissions they cannot send the application\'s stop command.',
            ],
            'list_files' => [
                'title' => 'List Files',
                'description' => 'Allows user to list all files and folders on the server but not view file contents.',
            ],
            'edit_files' => [
                'title' => 'Edit Files',
                'description' => 'Allows user to open a file for viewing only.',
            ],
            'save_files' => [
                'title' => 'Save Files',
                'description' => 'Allows user to save modified file contents.',
            ],
            'move_files' => [
                'title' => 'Rename & Move Files',
                'description' => 'Allows user to move and rename files and folders on the filesystem.',
            ],
            'copy_files' => [
                'title' => 'Copy Files',
                'description' => 'Allows user to copy files and folders on the filesystem.',
            ],
            'compress_files' => [
                'title' => 'Compress Files',
                'description' => 'Allows user to make archives of files and folders on the system.',
            ],
            'decompress_files' => [
                'title' => 'Decompress Files',
                'description' => 'Allows user to decompress .zip and .tar(.gz) archives.',
            ],
            'create_files' => [
                'title' => 'Create Files',
                'description' => 'Allows user to create a new file within the panel.',
            ],
            'upload_files' => [
                'title' => 'Upload Files',
                'description' => 'Allows user to upload files through the file manager.',
            ],
            'delete_files' => [
                'title' => 'Delete Files',
                'description' => 'Allows user to delete files from the system.',
            ],
            'download_files' => [
                'title' => 'Download Files',
                'description' => 'Allows user to download files. If a user is given this permission they can download and view file contents even if that permission is not assigned on the panel.',
            ],
            'list_subusers' => [
                'title' => 'List Subusers',
                'description' => 'Allows user to view a listing of all subusers assigned to the server.',
            ],
            'view_subuser' => [
                'title' => 'View Subuser',
                'description' => 'Allows user to view permissions assigned to subusers.',
            ],
            'edit_subuser' => [
                'title' => 'Edit Subuser',
                'description' => 'Allows a user to edit permissions assigned to other subusers.',
            ],
            'create_subuser' => [
                'title' => 'Create Subuser',
                'description' => 'Allows user to create additional subusers on the server.',
            ],
            'delete_subuser' => [
                'title' => 'Delete Subuser',
                'description' => 'Allows a user to delete other subusers on the server.',
            ],
            'set_connection' => [
                'title' => 'Set Default Connection',
                'description' => 'Allows user to set the default connection used for a server as well as view avaliable ports.',
            ],
            'view_startup' => [
                'title' => 'View Startup Command',
                'description' => 'Allows user to view the startup command and associated variables for a server.',
            ],
            'edit_startup' => [
                'title' => 'Edit Startup Command',
                'description' => 'Allows a user to modify startup variables for a server.',
            ],
            'list_tasks' => [
                'title' => 'List Tasks',
                'description' => 'Allows a user to list all tasks (enabled and disabled) on a server.',
            ],
            'view_task' => [
                'title' => 'View Task',
                'description' => 'Allows a user to view a specific task\'s details.',
            ],
            'toggle_task' => [
                'title' => 'Toggle Task',
                'description' => 'Allows a user to toggle a task on or off.',
            ],
            'queue_task' => [
                'title' => 'Queue Task',
                'description' => 'Allows a user to queue a task to run on next cycle.',
            ],
            'create_task' => [
                'title' => 'Create Task',
                'description' => 'Allows a user to create new tasks.',
            ],
            'delete_task' => [
                'title' => 'Delete Task',
                'description' => 'Allows a user to delete a task.',
            ],
            'view_sftp' => [
                'title' => 'View SFTP Details',
                'description' => 'Allows user to view the server\'s SFTP information but not the password.',
            ],
            'view_sftp_password' => [
                'title' => 'View SFTP Password',
                'description' => 'Allows user to view the SFTP password for the server.',
            ],
            'reset_sftp' => [
                'title' => 'Reset SFTP Password',
                'description' => 'Allows user to change the SFTP password for the server.',
            ],
            'view_databases' => [
                'title' => 'View Database Details',
                'description' => 'Allows user to view all databases associated with this server including the usernames and passwords for the databases.',
            ],
            'reset_db_password' => [
                'title' => 'Reset Database Password',
                'description' => 'Allows a user to reset passwords for databases.',
            ],
        ],
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
        'add_folder' => 'Add New Folder',
        'edit' => [
            'header' => 'Edit File',
            'header_sub' => 'Make modifications to a file from the web.',
            'save' => 'Save File',
            'return' => 'Return to File Manager',
        ],
        'add' => [
            'header' => 'New File',
            'header_sub' => 'Create a new file on your server.',
            'name' => 'File Name',
            'create' => 'Create File',
        ],
    ],
    'config' => [
        'startup' => [
            'header' => 'Start Configuration',
            'header_sub' => 'Control server startup arguments.',
            'command' => 'Startup Command',
            'edit_params' => 'Edit Parameters',
            'update' => 'Update Startup Parameters',
            'startup_var' => 'Startup Command Variable',
            'startup_regex' => 'Input Rules',
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
            'help_text' => 'The list to the left includes all available IPs and ports that are open for your server to use for incoming connections.',
        ],
    ],
];
