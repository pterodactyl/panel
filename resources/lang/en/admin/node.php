<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'validation' => [
        'fqdn_not_resolvable' => 'The FQDN or IP address provided does not resolve to a valid IP address.',
        'fqdn_required_for_ssl' => 'A fully qualified domain name that resolves to a public IP address is required in order to use SSL for this node.',
    ],
    'notices' => [
        'allocations_added' => 'Allocations have successfully been added to this node.',
        'node_deleted' => 'Node has been successfully removed from the panel.',
        'location_required' => 'You must have at least one location configured before you can add a node to this panel.',
        'node_created' => 'Successfully created new node. You can automatically configure the daemon on this machine by visiting the \'Configuration\' tab. <strong>Before you can add any servers you must first allocate at least one IP address and port.</strong>',
        'node_updated' => 'Node information has been updated. If any daemon settings were changed you will need to reboot it for those changes to take effect.',
        'unallocated_deleted' => 'Deleted all un-allocated ports for <code>:ip</code>.',
    ],
    'index' => [
        'header' => [
            'title' => 'List Nodes',
            'overview' => 'Nodes<small>All nodes available on the system.</small>',
            'admin' => 'Admin',
            'nodes' => 'Nodes',
        ],
        'content' => [
            'node_list' => 'Node List',
            'create_new' => 'Create New',
            'name' => 'Name',
            'location' => 'Location',
            'memory' => 'Memory',
            'disk' => 'Disk',
            'servers' => 'Servers',
            'ssl' => 'SSL',
            'public' => 'Public',
            'error' => 'Error connecting to node! Check browser console for details.',
        ],
    ],
    'new' => [
        'header' => [
            'title' => 'Nodes &rarr; New',
            'overview' => 'New Node<small>Create a new local or remote node for servers to be installed to.</small>',
            'new' => 'New',
        ],
        'content' => [
            'basic' => 'Basic Details',
            'limit' => 'Character limits: <code>a-zA-Z0-9_.-</code> and <code>[Space]</code> (min 1, max 100 characters).',
            'description' => 'Description',
            'visibility' => 'Node Visibility',
            'public' => 'Public',
            'private' => 'Private',
            'private_hint' => 'By setting a node to <code>private</code> you will be denying the ability to auto-deploy to this node.',
            'fqdn' => 'FQDN',
            'fqdn_hint' => 'Please enter domain name (e.g <code>node.example.com</code>) to be used for connecting to the daemon. An IP address may be used <em>only</em> if you are not using SSL for this node.',
            'ssl' => 'Communicate Over SSL',
            'use_ssl' => 'Use SSL Connection',
            'use_http' => 'Use HTTP Connection',
            'ssl_hint' => 'Your Panel is currently configured to use a secure connection. In order for browsers to connect to your node it <strong>must</strong> use a SSL connection.',
            'http_hint' => 'In most cases you should select to use a SSL connection. If using an IP Address or you do not wish to use SSL at all, select a HTTP connection.',
            'proxy_on' => 'Behind Proxy',
            'proxy_off' => 'Not Behind Proxy',
            'proxy_hint' => 'If you are running the daemon behind a proxy such as Cloudflare, select this to have the daemon skip looking for certificates on boot.',
            'configuration' => 'Configuration',
            'file_dir' => 'Daemon Server File Directory',
            'file_dir_hint' => 'Enter the directory where server files should be stored. <strong>If you use OVH you should check your partition scheme. You may need to use <code>/home/daemon-data</code> to have enough space.</strong>',
            'total_memory' => 'Total Memory',
            'memory_overallocation' => 'Memory Over-Allocation',
            'memory_overallocation_hint' => 'Enter the total amount of memory available for new servers. If you would like to allow overallocation of memory enter the percentage that you want to allow. To disable checking for overallocation enter <code>-1</code> into the field. Entering <code>0</code> will prevent creating new servers if it would put the node over the limit.',
            'total_disk', => 'Total Disk Space',
            'disk_overallocation' => 'Disk Over-Allocation',
            'disk_overallocation_hint' => 'Enter the total amount of disk space available for new servers. If you would like to allow overallocation of disk space enter the percentage that you want to allow. To disable checking for overallocation enter <code>-1</code> into the field. Entering <code>0</code> will prevent creating new servers if it would put the node over the limit.',
            'sftp_port' => 'Daemon SFTP Port',
            'sftp_port_hint' => 'The daemon runs its own SFTP management container and does not use the SSHd process on the main physical server. <Strong>Do not use the same port that you have assigned for your physical server’s SSH process.</strong> If you will be running the daemon behind CloudFlare&reg; you should set the daemon port to <code>8443</code> to allow websocket proxying over SSL.',
            'create_node' => 'Create Node',
        ],
    ],
];
