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
        'title' => 'Nests &rarr; New Egg',
        'overview' => 'New Egg<small>Create a new Egg to assign to servers.</small>',
        'admin' => 'Admin',
        'nests' => 'Nests',
        'new_egg' => 'New Egg',
    ],
    'content' => [
        'configuration' => 'Configuration',
        'associated_nest' => 'Associated Nest',
        'associated_nest_description' => 'Think of a Nest as a category. You can put multiple Eggs in a nest, but consider putting only Eggs that are related to each other in each Nest.',
        'name_description' => 'A simple, human-readable name to use as an identifier for this Egg. This is what users will see as their game server type.',
        'description' => 'Description',
        'description_description' => 'A description of this Egg.',
        'docker' => 'Docker Image',
        'docker_description' => 'The default docker image that should be used for new servers using this Egg. This can be changed per-server.',
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