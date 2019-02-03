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
        'title' => 'Nests',
        'overview' => 'Nests<small>All nests currently available on this system.</small>',
        'admin' => 'Admin',
        'locations' => 'Locations',
    ],
    'content' => [
        'alert' => 'Eggs are a powerful feature of Pterodactyl Panel that allow for extreme flexibility and configuration. Please note that while powerful, modifying an egg wrongly can very easily brick your servers and cause more problems. Please avoid editing our default eggs — those provided by <code>support@pterodactyl.io</code> — unless you are absolutely sure of what you are doing.'
        'configured_nests' => 'Configured Nests',
        'import_egg' => 'Import Egg',
        'create_new' => 'Create New',
    ],

    'notices' => [
        'created' => 'A new nest, :name, has been successfully created.',
        'deleted' => 'Successfully deleted the requested nest from the Panel.',
        'updated' => 'Successfully updated the nest configuration options.',
    ],
    'eggs' => [
        'notices' => [
            'imported' => 'Successfully imported this Egg and its associated variables.',
            'updated_via_import' => 'This Egg has been updated using the file provided.',
            'deleted' => 'Successfully deleted the requested egg from the Panel.',
            'updated' => 'Egg configuration has been updated successfully.',
            'script_updated' => 'Egg install script has been updated and will run whenever servers are installed.',
            'egg_created' => 'A new egg was laid successfully. You will need to restart any running daemons to apply this new egg.',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => 'The variable ":variable" has been deleted and will no longer be available to servers once rebuilt.',
            'variable_updated' => 'The variable ":variable" has been updated. You will need to rebuild any servers using this variable in order to apply changes.',
            'variable_created' => 'New variable has successfully been created and assigned to this egg.',
        ],
    ],
];
