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
        'title' => 'Locations',
        'overview' => 'Locations<small>All locations that nodes can be assigned to for easier categorization.',
        'admin' => 'Admin',
        'locations' => 'Locations',
    ],
    'content' => [
        'location_list' => 'Location List',
        'create_new' => 'Create New',
        'id' => 'ID',
        'short_code' => 'Short Code',
        'description' => 'Description',
        'nodes' => 'Nodes',
        'servers' => 'Servers',
        'create_location' => 'Create Location',
        'short_code_description' => 'A short identifier used to distinguish this location from others. Must be between 1 and 60 characters, for example, <code>us.nyc.lvl3</code>.',
        'description' => 'Description',
        'description_description' => 'A longer description of this location. Must be less than 255 characters.',
        'cancel' => 'cancel',
        'create' => 'create',
    ],
    'view' => [
        'header' => [
            'title' => 'Locations &rarr; View &rarr;',
            'overview' => 'Locations<small>All locations that nodes can be assigned to for easier categorization.',
            'admin' => 'Admin',
            'locations' => 'Locations',
        ],
        'content' => [
            'location_details' => 'Location Details',
            'save' => 'Save',
            'name' => 'Name',
            'fqdn' => 'FQDN',
        ],
    ],
];