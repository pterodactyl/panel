<?php

declare(strict_types=1);

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
        'ip-blocked' => 'Blocked request from unlisted IP address for :identifier',
        'sftp' => [
            'fail' => 'Failed SFTP log in',
        ],
    ],
    'user' => [
        'user' => [
            'create' => 'Created a new user :email',
        ],
        'account' => [
            'email-changed' => 'Changed email from :old to :new',
            'password-changed' => 'Changed password',
        ],
        'api-key' => [
            'create' => 'Created new API key :identifier',
            'delete' => 'Deleted API key :identifier',
        ],
        'ssh-key' => [
            'create' => 'Added SSH key :fingerprint to account',
            'delete' => 'Removed SSH key :fingerprint from account',
        ],
        'two-factor' => [
            'create' => 'Enabled two-factor auth',
            'delete' => 'Disabled two-factor auth',
        ],
    ],
    'server' => [
        'reinstall' => 'Reinstalled server',
        'console' => [
            'command' => 'Executed ":command" on the server',
        ],
        'power' => [
            'start' => 'Started the server',
            'stop' => 'Stopped the server',
            'restart' => 'Restarted the server',
            'kill' => 'Killed the server process',
        ],
        'backup' => [
            'download' => 'Downloaded the :name backup',
            'delete' => 'Deleted the :name backup',
            'restore' => 'Restored the :name backup (deleted files: :truncate)',
            'restore-complete' => 'Completed restoration of the :name backup',
            'restore-failed' => 'Failed to complete restoration of the :name backup',
            'start' => 'Started a new backup :name',
            'complete' => 'Marked the :name backup as complete',
            'fail' => 'Marked the :name backup as failed',
            'lock' => 'Locked the :name backup',
            'unlock' => 'Unlocked the :name backup',
        ],
        'database' => [
            'create' => 'Created new database :name',
            'rotate-password' => 'Password rotated for database :name',
            'delete' => 'Deleted database :name',
        ],
        'file' => [
            'compress_one' => 'Compressed :directory:files.0',
            'compress_other' => 'Compressed :count files in :directory',
            'chmod_one' => 'Changed the permissions of :directory:files.0.file to :files.0.mode',
            'chmod_other' => 'Changed the permissions of :count files in :directory',
            'read' => 'Viewed the contents of :file',
            'copy' => 'Created a copy of :file',
            'create-directory' => 'Created directory :directory:name',
            'decompress' => 'Decompressed :files in :directory',
            'delete_one' => 'Deleted :directory:files.0',
            'delete_other' => 'Deleted :count files in :directory',
            'download' => 'Downloaded :file',
            'pull' => 'Downloaded a remote file from :url to :directory',
            'rename_one' => 'Renamed :directory:files.0.from to :directory:files.0.to',
            'rename_other' => 'Renamed :count files in :directory',
            'write' => 'Wrote new content to :file',
            'upload' => 'Began a file upload',
            'uploaded' => 'Uploaded :directory:file',
        ],
        'sftp' => [
            'denied' => 'Blocked SFTP access due to permissions',
            'create_one' => 'Created :files.0',
            'create_other' => 'Created :count new files',
            'write_one' => 'Modified the contents of :files.0',
            'write_other' => 'Modified the contents of :count files',
            'delete_one' => 'Deleted :files.0',
            'delete_other' => 'Deleted :count files',
            'create-directory_one' => 'Created the :files.0 directory',
            'create-directory_other' => 'Created :count directories',
            'rename_one' => 'Renamed :files.0.from to :files.0.to',
            'rename_other' => 'Renamed or moved :count files',
        ],
        'allocation' => [
            'create' => 'Added :allocation to the server',
            'notes' => 'Updated the notes for :allocation from ":old" to ":new"',
            'primary' => 'Set :allocation as the primary server allocation',
            'delete' => 'Deleted the :allocation allocation',
        ],
        'schedule' => [
            'create' => 'Created the :name schedule',
            'update' => 'Updated the :name schedule',
            'execute' => 'Manually executed the :name schedule',
            'delete' => 'Deleted the :name schedule',
        ],
        'task' => [
            'create' => 'Created a new ":action" task for the :name schedule',
            'update' => 'Updated the ":action" task for the :name schedule',
            'delete' => 'Deleted a task for the :name schedule',
        ],
        'settings' => [
            'rename' => 'Renamed the server from :old to :new',
            'description' => 'Changed the server description from :old to :new',
        ],
        'startup' => [
            'edit' => 'Changed the :variable variable from ":old" to ":new"',
            'image' => 'Updated the Docker Image for the server from :old to :new',
        ],
        'subuser' => [
            'create' => 'Added :email as a subuser',
            'update' => 'Updated the subuser permissions for :email',
            'delete' => 'Removed :email as a subuser',
        ],
    ],
    'admin' => [
        'api-key' => [
            'create' => 'Created application API key :identifier',
            'update' => 'Updated application API key :identifier',
            'delete' => 'Deleted application API key :identifier',
        ],
        'database-host' => [
            'create' => 'Created the :name database host',
            'update' => 'Updated the :name database host',
            'delete' => 'Deleted the :name database host',
        ],
        'egg' => [
            'create' => 'Created the :name egg',
            'update' => 'Updated the :name egg',
            'delete' => 'Deleted the :name egg',
            'import' => 'Imported the :name egg',
            'update-import' => 'Updated the :name egg from an import',
            'scripts' => 'Updated the install script for the :name egg',
            'tags' => 'Updated the tags for an egg',
            'tags_one' => 'Set the egg tags to :tags.0',
            'tags_other' => 'Set :count tags on the egg',
        ],
        'egg-variable' => [
            'create' => 'Created the :name egg variable',
            'update' => 'Updated the :name egg variable',
            'delete' => 'Deleted the :name egg variable',
            'reorder' => 'Reordered the variables for an egg',
        ],
        'location' => [
            'create' => 'Created the :short location',
            'update' => 'Updated the :short location',
            'delete' => 'Deleted the :short location',
        ],
        'mount' => [
            'create' => 'Created the :name mount',
            'update' => 'Updated the :name mount',
            'delete' => 'Deleted the :name mount',
            'attach-egg' => 'Attached a mount to eggs :eggs',
            'attach-egg_one' => 'Attached an egg to the mount',
            'attach-egg_other' => 'Attached :count eggs to the mount',
            'detach-egg' => 'Detached a mount from egg :egg',
            'attach-node' => 'Attached a mount to nodes :nodes',
            'attach-node_one' => 'Attached a node to the mount',
            'attach-node_other' => 'Attached :count nodes to the mount',
            'detach-node' => 'Detached a mount from node :node',
        ],
        'node' => [
            'create' => 'Created the :name node',
            'update' => 'Updated the :name node',
            'delete' => 'Deleted the :name node',
            'deploy-token' => 'Generated a deployment token for the :name node',
            'tags' => 'Updated the egg tags for a node',
            'tags_one' => 'Set the node tags to :tags.0',
            'tags_other' => 'Set :count tags on the node',
            'deployment-tags' => 'Updated the deployment tags for a node',
            'deployment-tags_one' => 'Set the node deployment tags to :tags.0',
            'deployment-tags_other' => 'Set :count deployment tags on the node',
        ],
        'node-allocation' => [
            'create' => 'Added :ports_count ports across :address_count addresses to the node',
            'alias' => 'Set an allocation alias to :alias',
            'delete' => 'Deleted port :port on :address',
            'delete-block' => 'Deleted the unassigned allocations on :address',
        ],
        'server' => [
            'create' => 'Created the :name server',
            'delete' => 'Deleted the :name server',
            'details' => 'Updated the details for :name',
            'build' => 'Updated the build configuration for :name',
            'startup' => 'Updated the startup configuration for :name',
            'reinstall' => 'Reinstalled :name',
            'rebuild' => 'Triggered a rebuild of :name',
            'suspend' => 'Suspended :name',
            'unsuspend' => 'Unsuspended :name',
            'toggle-install' => 'Toggled the install status of :name',
            'transfer' => 'Started transferring :name to node :node_id',
            'mount' => 'Added the :name mount to a server',
            'unmount' => 'Removed the :name mount from a server',
        ],
        'server-backup' => [
            'toggle-lock' => 'Toggled the lock on the :name backup',
        ],
        'server-database' => [
            'create' => 'Created the :name database',
            'delete' => 'Deleted the :name database',
            'rotate-password' => 'Rotated the password for the :name database',
        ],
        'tag' => [
            'create' => 'Created the :slug tag',
            'update' => 'Updated the :slug tag',
            'delete' => 'Deleted the :slug tag',
        ],
        'user' => [
            'create' => 'Created the user :email',
            'update' => 'Updated the user :email',
            'delete' => 'Deleted the user :email',
            'disable-2fa' => 'Disabled two-factor authentication for :email',
        ],
    ],
];
