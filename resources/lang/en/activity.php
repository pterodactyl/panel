<?php

/**
 * Contains all of the translation strings for different activity log
 * events. These should be keyed by the value in front of the colon (:)
 * in the event name. If there is no colon present, they should live at
 * the top level.
 */
return [
    'auth' => [
        'password-reset' => 'Reset account password',
        'reset-password' => 'Sending password reset email',
        'checkpoint' => 'Prompting for second factor authentication',
        'recovery-token' => 'Used recovery token as second factory for login',
        'token' => 'Provided valid second factor authentication token for login',
    ],
    'user' => [
        'account' => [
            'email-changed' => 'Updated account email from <b>:old</b> to <b>:new</b>',
            'password-changed' => 'Updated account password',
        ],
        'api-key' => [
            'create' => 'Created new API key <b>:identifier</b>',
            'delete' => 'Deleted API key <b>:identifier</b>',
        ],
        'ssh-key' => [
            'create' => 'Added SSH key (<b>:fingerprint</b>) to account',
            'delete' => 'Removed SSH key (<b>:fingerprint</b>) from account',
        ],
        'two-factor' => [
            'create' => 'Enabled two-factor authentication for account',
            'delete' => 'Disabled two-factor authentication for account',
        ],
    ],
    'server' => [
        'backup' => [
            'download' => 'Downloaded the <b>:name</b> backup',
            'delete' => 'Deleted the <b>:name</b> backup',
            'restore' => 'Restored the <b>:name</b> backup (deleted files: :truncate)',
            'restore-complete' => 'Completed restoration of the <b>:name</b> backup',
            'restore-failed' => 'Failed to complete restoration of the <b>:name</b> backup',
            'start' => 'Started a new backup <b>:name</b>',
            'complete' => 'Marked the <b>:name</b> backup as complete',
            'fail' => 'Marked the <b>:name</b> backup as failed',
            'lock' => 'Locked the <b>:name</b> backup',
            'unlock' => 'Unlocked the <b>:name</b> backup',
        ],
        'database' => [
            'create' => 'Created new database <b>:name</b>',
            'rotate-password' => 'Password rotated for database <b>:name</b>',
            'delete' => 'Deleted database <b>:name</b>',
        ],
        'file' => [
            'compress' => 'Created new file archive of files in <b>:directory</b>',
            'read' => 'Viewed the contents of <b>:file</b>',
            'copy' => 'Created a copy of <b>:file</b>',
            'create-directory' => 'Created a new directory <b>:name</b> in <b>:directory</b>',
            'decompress' => 'Decompressed a file archive in <b>:directory</b>',
            'delete' => 'Deleted files in <b>:directory</b>',
            'download' => 'Downloaded <b>:file</b>',
            'pull' => 'Downloaded a remote file from :url to <b>:directory</b>',
            'rename' => 'Renamed files in <b>:directory</b>',
            'write' => 'Wrote new content to <b>:file</b>',
            'upload' => 'Began a file upload',
        ],
        'allocation' => [
            'create' => 'Added <b>:allocation</b> to the server',
            'notes' => 'Updated the notes for <b>:allocation</b> from ":old" to ":new"',
            'primary' => 'Set <b>:allocation</b> as the primary server allocation',
            'delete' => 'Deleted the <b>:allocation</b> allocation',
        ],
        'schedule' => [
            'store' => 'Created the <b>:name</b> schedule',
            'update' => 'Updated the <b>:name</b> schedule',
            'execute' => 'Manually executed the <b>:name</b> schedule',
            'delete' => 'Deleted the <b>:name</b> schedule',
        ],
        'task' => [
            'create' => 'Created a new ":action" task for the <b>:name</b> schedule',
            'update' => 'Updated the ":action" task for the <b>:name</b> schedule',
            'delete' => 'Deleted a task for the <b>:name</b> schedule',
        ],
        'settings' => [
            'rename' => 'Renamed the server from <b>:old</b> to <b>:new</b>',
            'reinstall' => 'Triggered a server reinstall',
        ],
        'startup' => [
            'edit' => 'Edited the <b>:variable</b> startup variable for the server from ":old" to ":new"',
            'image' => 'Updated the Docker Image for the server from <b>:old</b> to <b>:new</b>',
        ],
        'subuser' => [
            'create' => 'Added <b>:email</b> as a subuser',
            'update' => 'Updated the subuser permissions for <b>:email</b>',
            'delete' => 'Removed <b>:email</b> as a subuser',
        ],
    ],
];
