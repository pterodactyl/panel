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
        'title' => 'List Servers',
        'overview' => 'Servers<small>All servers available on the system.</small>',
    ],
    'content' => [
        'server_list' => 'Server List'
        'create_new' => 'Create New'
        'server_name' => 'Server Name',
        'uuid' => 'UUID',
        'owner' => 'Owner',
        'node' => 'Node',
        'conn' => 'Connection',
        'active' => 'Active',
    ],
    'new' => [
        'header' => [
            'title' => 'New Server',
            'overview' => 'Create Server<small>Add a new server to the panel.</small>',
            'create_server' => 'Create Server',
        ],
        'content' => [
            'core_details' => 'Core Details',
            'character_limit' => 'Character limits: <code>a-z A-Z 0-9 _ - .</code> and <code>[Space]</code> (max 200 characters).',
            'start_when_installed' => 'Start Server when Installed',
            'node' => 'Node',
            'node_description' => 'The node which this server will be deployed to.',
            'default_alloc' => 'Default Allocation',
            'default_alloc_hint' => 'The main allocation that will be assigned to this server.',
            'additional_alloc' => 'Additional Allocation(s)',
            'additional_alooc_hint' => 'Additional allocations to assign to this server on creation.',
            'resouce_manage' => 'Resource Management',
            'swap' => 'Swap',
            'swap_hint' => 'If you do not want to assign swap space to a server, simply put <code>0</code> for the value, or <code>-1</code> to allow unlimited swap space. If you want to disable memory limiting on a server, simply enter <code>0</code> into the memory field.',
            'io' => 'I/O',
            'io_hint' => 'If you do not want to limit CPU usage, set the value to <code>0</code>. To determine a value, take the number of <em>physical</em> cores and multiply it by 100. For example, on a quad core system <code>(4 * 100 = 400)</code> there is <code>400%</code> available. To limit a server to using half of a single core, you would set the value to <code>50</code>. To allow a server to use up to two physical cores, set the value to <code>200</code>. BlockIO should be a value between <code>10</code> and <code>1000</code>. Please see <a href="https://docs.docker.com/engine/reference/run/#/block-io-bandwidth-blkio-constraint" target="_blank">this documentation</a> for more information about it.',
            'nest_conf' => 'Nest Configuration',
            'nest' => 'Nest',
            'nest_hint' => 'Select the Nest that this server will be grouped under.',
            'egg_hint' => 'Select the Egg that will define how this server should operate.',
            'docker_conf' => 'Docker Configuration',
            'docker_image' => 'Docker Image',
            'docker_image_hint' => 'This is the default Docker image that will be used to run this server.',
            'startup_conf' => 'Startup Configuration',
            'startup_command_hint' => 'The following data substitutes are available for the startup command: <code>@{{SERVER_MEMORY}}</code>, <code>@{{SERVER_IP}}</code>, and <code>@{{SERVER_PORT}}</code>. They will be replaced with the allocated memory, server IP, and server port respectively.',
            'service_var' => 'Service Variables',
        ],
    ]
];
