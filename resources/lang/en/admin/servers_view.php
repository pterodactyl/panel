<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'header' => [
        'server' => 'Server',
        'title' => 'Build Details',
        'overview' => '<small>Control allocations and system resources for this server.</small>',
        'admin' => 'Admin',
        'servers' => 'Servers',
        'build_config' => 'Build Configuration',
    ],
    'content' => [
        'about' => 'About',
        'details' => 'Details',
        'startup' => 'Startup',
        'database' => 'Database',
        'manage' => 'Manage',
        'delete' => 'Delete',
        'sys_resources' => 'System Resources',
        'alloc_memory' => 'Allocated Memory',
        'alloc_memory_hint' => 'The maximum amount of memory allowed for this container. Setting this to <code>0</code> will allow unlimited memory in a container.',
        'alloc_swap' => 'Allocated Swap',
        'alloc_swap_hint' => 'Setting this to <code>0</code> will disable swap space on this server. Setting to <code>-1</code> will allow unlimited swap.',
        'cpu_limit' => 'CPU Limit',
        'cpu_limit_hint' => 'Each <em>physical</em> core on the system is considered to be <code>100%</code>. Setting this value to <code>0</code> will allow a server to use CPU time without restrictions.',
        'block_io' => 'Block IO Proportion',
        'block_io_hint' => 'Changing this value can have negative effects on all containers on the system. We strongly recommend leaving this value as <code>500</code>.',
        'space_limit' => 'Disk Space Limit',
        'space_limit_hint' => 'This server will not be allowed to boot if it is using more than this amount of space. If a server goes over this limit while running it will be safely stopped and locked until enough space is available.',
        'feature_limit' => 'Application Feature Limits',
        'db_limit' => 'Database Limit',
        'db_limit_hint' => 'The total number of databases a user is allowed to create for this server. Leave blank to allow unlimited.',
        'alloc_limit' => 'Allocation Limit',
        'alloc_limit_hint' => '<strong>This feature is not currently implemented.</strong> The total number of allocations a user is allowed to create for this server. Leave blank to allow unlimited.',
        'alloc_manage' => 'Allocation Management',
        'game_port' => 'Game Port',
        'game_port_hint' => 'The default connection address that will be used for this game server.',
        'assign_ports' => 'Assign Additional Ports' ,
        'assign_ports_hint' => 'Please note that due to software limitations you cannot assign identical ports on different IPs to the same server.',
        'remove_ports' => 'Remove Additional Ports',
        'remove_ports_hint' => 'Simply select which ports you would like to remove from the list above. If you want to assign a port on a different IP that is already in use you can select it from the left and delete it here.',
        'update_config' => 'Update Build Configuration',
    ],
    'database' => [
        'header' => [
            'title' => 'Databases',
            'overview' => 'Manage server databases.',
        ],
        'content' => [
            'pw_infoStart' => 'Database passwords can be viewed when ',
            'pw_infoEnd' => 'visiting this server</a> on the front-end.',
            'active_db' => 'Active Databases',
            'username' => 'Username',
            'conn_from' => 'Connections From',
            'host' => 'Host',
            'create_new_db' => 'Create New Database',
            'db_host' => 'Database Host',
            'db_host_hint' => 'Select the host database server that this database should be created on.',
            'conn' => 'Connections',
            'conn_hint' => 'This should reflect the IP address that connections are allowed from. Uses standard MySQL notation. If unsure leave as <code>%</code>.','auth_hint' => 'A username and password for this database will be randomly generated after form submission.',
            'warning' => 'Are you sure that you want to delete this database? There is no going back, all data will immediately be removed.',
            'ooopsi' => 'Whoops!',
            'error' => 'An error occurred while processing this request.',
            'success' => 'The password for this database has been reset.',
        ],
    ],
    'delete' => [
        'header' => [
            'overview' => 'Delete this server from the panel.',
        ],
        'content' => [
            'safe_del' => 'Safely Delete Server',
            'safe_del_hint' => 'This action will attempt to delete the server from both the panel and daemon. If either one reports an error the action will be cancelled.</p>
            <p class="text-danger small">Deleting a server is an irreversible action. <strong>All server data</strong> (including files and users) will be removed from the system.',
            'safe_del_confirm' => 'Safely Delete This Server',
            'force_del' => 'Force Delete Server',
            'force_del_hint' => 'This action will attempt to delete the server from both the panel and daemon. If the daemon does not respond, or reports an error the deletion will continue.</p>
            <p class="text-danger small">Deleting a server is an irreversible action. <strong>All server data</strong> (including files and users) will be removed from the system. This method may leave dangling files on your daemon if it reports an error.',
            'force_del_confirm' => 'Forcibly Delete This Server',
            'warning' => 'Are you sure that you want to delete this server? There is no going back, all data will immediately be removed.',
        ],
    ],
    'details' => [
        'header' => [
            'overview' => 'Edit details for this server including owner and container.',
        ],
        'content' => [
            'base_info' => 'Base Information',
            'server_name' => 'Server Name',
            'character_limit' => 'Character limits: <code>a-zA-Z0-9_-</code> and <code>[Space]</code> (max 35 characters).',
            'ext_id' => 'External Identifier',
            'ext_id_hint' => 'Leave empty to not assign an external identifier for this server. The external ID should be unique to this server and not be in use by any other servers.',
            'server_owner' => 'Server Owner',
            'server_owner_hint' => 'You can change the owner of this server by changing this field to an email matching another use on this system. If you do this a new daemon security token will be generated automatically.'
            'server_desc' => 'Server Description',
            'server_desc_hint' => 'A brief description of this server.',
            'update_details' => 'Update Details',
        ],
    ],
    'index' => [
        'information' => 'Information',
        'int_id' => 'Internal Identifier',
        'not_set' => 'Not Set',
        'uuid' => 'UUID / Docker Container ID',
        'service' => 'Service',
        'name' => 'Name',
        'memory' => 'Memory',
        'disk_space' => 'Disk Space',
        'block_io' => 'Block IO Weight',
        'cpu_limit' => 'CPU Limit',
        'default_conn' => 'Default Connection',
        'conn_alias' => 'Connection Alias',
        'no_alias' => 'No Alias Assigned',
        'suspended' => 'Suspended',
        'installing' => 'Installing',
        'install_failed' => 'Install Failed',
        'server_owner' => 'Server Owner',
        'more_info' => 'More info'
        'server_node' => 'Server Node'
    ],
    'manage' => [
        'header' => [
            'overview' => 'Additional actions to control this server.',
        ],
        'content' => [
            'reinstall' => 'Reinstall Server',
            'reinstall_hint' => 'This will reinstall the server with the assigned pack and service scripts. <strong>Danger!</strong> This could overwrite server data.',
            'install_properly' => 'Server Must Install Properly to Reinstall',
            'status' => 'Install Status',
            'status_hint' => 'If you need to change the install status from uninstalled to installed, or vice versa, you may do so with the button below.',
            'toggle_status' => 'Toggle Install Status',
            'rebuild' => 'Rebuild Container',
            'rebuild_hint' => 'This will trigger a rebuild of the server container when it next starts up. This is useful if you modified the server configuration file manually, or something just didn’t work out correctly.',
            'rebuild_server' => 'Rebuild Server Container',
            'suspend' => 'Suspend Server',
            'suspend_hint' => 'This will suspend the server, stop any running processes, and immediately block the user from being able to access their files or otherwise manage the server through the panel or API.',
            'unsuspend' => 'Unsuspend Server',
            'unsuspend_hint' => 'This will unsuspend the server and restore normal user access.',
        ],
    ],
    'startup' => [
        'header' => [
            'overview' => 'Control startup command as well as variables.',
        ],
        'content' => [
            'startup_command_modify' => 'Startup Command Modification',
            'startup_command' => 'Startup Command',
            'startup_command_hint' => 'Edit your server’s startup command here. The following variables are available by default:'
            'default_command' => 'Default Service Start Command',
            'save_modification' => 'Save Modifications',
            'service_conf' => 'Service Configuration',
            'service_confDangerStart' => 'Changing any of the below values will result in the server processing a re-install command. The server will be stopped and will then proceed.
            If you are changing the pack, existing data <em>may</em> be overwritten. If you would like the service scripts to not run, ensure the box is checked at the bottom.',
            'service_confDangerEnd' => 'This is a destructive operation in many cases. This server will be stopped immediately in order for this action to proceed.',
            'select_nest' => 'Select the Nest that this server will be grouped into.',
            'egg' => 'Egg',
            'egg_hint' => 'Select the Egg that will provide processing data for this server.',
            'data_pack' => 'Data Pack',
            'data_pack_hint' => 'Select a data pack to be automatically installed on this server when first created.',
            'skip_script' => 'Skip Egg Install Script',
            'skip_script_hint' => 'If the selected Egg has an install script attached to it, the script will run during install after the pack is installed. If you would like to skip this step, check this box.'
            'docker_conf' => 'Docker Container Configuration',
            'image' => 'Image',
            'image_hint' => 'The Docker image to use for this server. The default image for the selected egg is <code id="setDefaultImage"></code>.',
            'select_sp' => 'Select a Service Pack'
            'select_egg' => 'Select a Nest Egg',
            'error' => 'ERROR: Startup Not Defined!',
            'no_sp' => 'No Service Pack',
            'required' => 'Required',
            'startup_var' => 'Startup Command Variable:',
            'input_rules' => 'Input Rules:',
        ],
    ],
];