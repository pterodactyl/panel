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
        'title' => 'Database Hosts',
        'overview' => 'Database Hosts<small>Database hosts that servers can have databases created on.</small>',
        'admin' => 'Admin',
        'dbhost' => 'Database Hosts',
    ],
    'content' => [
        'list' => 'Host List',
        'new' => 'Create New',
        'id' => 'ID',
        'name' => 'Name', 
        'host' => 'Host',
        'port' => 'Port',
        'username' => 'Username',
        'database' => 'Database',
        'node' => 'Node',
        'none' => 'None',
        'create_new' => 'Create New Database Host',
        'create_new_name' => 'Name',
        'create_new_description' => 'A short identifier used to distinguish this location from others. Must be between 1 and 60 characters, for example, <code>us.nyc.lvl3</code>.',
        'create_new_host' => 'Host',
        'create_new_host_description' => 'The IP address or FQDN that should be used when attempting to connect to this MySQL host <em>from the panel</em> to add new databases.',
        'create_new_port' => 'Port',
        'create_new_port_description' => 'The port that MySQL is running on for this host.',
        'create_new_username' => 'Username',
        'create_new_username_description' => 'The username of an account that has enough permissions to create new users and databases on the system.',
        'create_new_password' => 'Password',
        'create_new_password_description' => 'The password to the account defined.',
        'linked_node' => 'Password',
        'linked_node_description' => 'This setting does nothing other than default to this database host when adding a database to a server on the selected node.',
        'footer' => 'The account defined for this database host <strong>must</strong> have the <code>WITH GRANT OPTION</code> permission. If the defined account does not have this permission requests to create databases <em>will</em> fail. <strong>Do not use the same account details for MySQL that you have defined for this panel.</strong>',
        'cancel' => 'Cancel',
        'create' => 'Create',
    ],
    'view' => [
        'header' => [
            'title' => 'Database Hosts &rarr; View &rarr;',
            'overview' => '<small>Viewing associated databases and details for this database host.</small>',
        ],
        'content' => [
            'host_details' => 'Host Details',
            'user_details' => 'User Details',
            'databases' => 'Databases',
            'server' => 'Server',
            'dbname' => 'Database Name',
            'connections_from' => 'Connections From',
            'manage' => 'Manage',
        ],
    ],
];