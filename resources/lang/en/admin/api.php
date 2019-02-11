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
        'title' => 'Application API',
        'overview' => 'Application API<small>Control access credentials for managing this Panel via the API.</small>',
        'admin' => 'Admin',
        'api' => 'Application API',
    ],
    'content' => [
        'list' => 'Credentials List',
        'new' => 'Create New',
    ],
    'revoke' => [
        'revoke' => 'Revoke API Key',
        'warning' => 'Once this API key is revoked any applications currently using it will stop working.',
        'success' => 'API Key has been revoked.',
        'ooopsi' => 'Whoops!',
        'error' => 'An error occurred while attempting to revoke this key.',
    ],
    'new' => [
        'header' => [
            'title' => 'Application API<small>Create a new application API key.</small>',
            'new' => 'New Credentials',
        ],
        'content' => [
            'setperms' => 'Select Permissions',
            'r' => 'Read',
            'rw' => 'Read &amp; Write',
            'none' => 'None',
            'description' => 'Description ',
            'description_text' => 'Once you have assigned permissions and created this set of credentials you will be unable to come back and edit it. If you need to make changes down the road you will need to create a new set of credentials.',
            'create' => 'Create Credentials',
        ],
    ],
];
