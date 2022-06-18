<?php

/**
 * Contains all of the translation strings for different activity log
 * events. These should be keyed by the value in front of the colon (:)
 * in the event name. If there is no colon present, they should live at
 * the top level.
 */
return [
    'auth' => [
        'fail' => 'Failed log in',
        'success' => 'Logged in',
        'password-reset' => 'Password reset',
        'reset-password' => 'Requested password reset',
        'checkpoint' => 'Two-factor authentication requested',
        'recovery-token' => 'Used two-factor recovery token',
        'token' => 'Solved two-factor challenge',
        'ip-blocked' => 'Blocked request from unlisted IP address for <strong>:identifier</strong>',
    ],
    'user' => [
        'account' => [
            'email-changed' => 'Changed email from <strong>:old</strong> to <strong>:new</strong>',
            'password-changed' => 'Changed password',
        ],
        'api-key' => [
            'create' => 'Created new API key <strong>:identifier</strong>',
            'delete' => 'Deleted API key <strong>:identifier</strong>',
        ],
        'ssh-key' => [
            'create' => 'Added SSH key <strong>:fingerprint</strong> to account',
            'delete' => 'Removed SSH key <strong>:fingerprint</strong> from account',
        ],
        'two-factor' => [
            'create' => 'Enabled two-factor auth',
            'delete' => 'Disabled two-factor auth',
        ],
    ],
    'server' => [
        'backup' => [
            'download' => 'Downloaded the <strong>:name</strong> backup',
            'delete' => 'Deleted the <strong>:name</strong> backup',
            'restore' => 'Restored the <strong>:name</strong> backup (deleted files: :truncate)',
            'restore-complete' => 'Completed restoration of the <strong>:name</strong> backup',
            'restore-failed' => 'Failed to complete restoration of the <strong>:name</strong> backup',
            'start' => 'Started a new backup <strong>:name</strong>',
            'complete' => 'Marked the <strong>:name</strong> backup as complete',
            'fail' => 'Marked the <strong>:name</strong> backup as failed',
            'lock' => 'Locked the <strong>:name</strong> backup',
            'unlock' => 'Unlocked the <strong>:name</strong> backup',
        ],
        'database' => [
            'create' => 'Created new database <strong>:name</strong>',
            'rotate-password' => 'Password rotated for database <strong>:name</strong>',
            'delete' => 'Deleted database <strong>:name</strong>',
        ],
        'file' => [
            'compress' => 'Created new file archive of files in <strong>:directory</strong>',
            'read' => 'Viewed the contents of <strong>:file</strong>',
            'copy' => 'Created a copy of <strong>:file</strong>',
            'create-directory' => 'Created a new directory <strong>:name</strong> in <strong>:directory</strong>',
            'decompress' => 'Decompressed a file archive in <strong>:directory</strong>',
            'delete' => 'Deleted files in <strong>:directory</strong>',
            'download' => 'Downloaded <strong>:file</strong>',
            'pull' => 'Downloaded a remote file from :url to <strong>:directory</strong>',
            'rename' => 'Renamed files in <strong>:directory</strong>',
            'write' => 'Wrote new content to <strong>:file</strong>',
            'upload' => 'Began a file upload',
        ],
        'allocation' => [
            'create' => 'Added <strong>:allocation</strong> to the server',
            'notes' => 'Updated the notes for <strong>:allocation</strong> from ":old" to ":new"',
            'primary' => 'Set <strong>:allocation</strong> as the primary server allocation',
            'delete' => 'Deleted the <strong>:allocation</strong> allocation',
        ],
        'schedule' => [
            'store' => 'Created the <strong>:name</strong> schedule',
            'update' => 'Updated the <strong>:name</strong> schedule',
            'execute' => 'Manually executed the <strong>:name</strong> schedule',
            'delete' => 'Deleted the <strong>:name</strong> schedule',
        ],
        'task' => [
            'create' => 'Created a new ":action" task for the <strong>:name</strong> schedule',
            'update' => 'Updated the ":action" task for the <strong>:name</strong> schedule',
            'delete' => 'Deleted a task for the <strong>:name</strong> schedule',
        ],
        'settings' => [
            'rename' => 'Renamed the server from <strong>:old</strong> to <strong>:new</strong>',
            'reinstall' => 'Triggered a server reinstall',
        ],
        'startup' => [
            'edit' => 'Edited the <strong>:variable</strong> startup variable for the server from ":old" to ":new"',
            'image' => 'Updated the Docker Image for the server from <strong>:old</strong> to <strong>:new</strong>',
        ],
        'subuser' => [
            'create' => 'Added <strong>:email</strong> as a subuser',
            'update' => 'Updated the subuser permissions for <strong>:email</strong>',
            'delete' => 'Removed <strong>:email</strong> as a subuser',
        ],
    ],
];
