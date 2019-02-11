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
    ],
    'content' => [
        'alert' => 'Eggs are a powerful feature of Pterodactyl Panel that allow for extreme flexibility and configuration. Please note that while powerful, modifying an egg wrongly can very easily brick your servers and cause more problems. Please avoid editing our default eggs — those provided by <code>support@pterodactyl.io</code> — unless you are absolutely sure of what you are doing.',
        'configured_nests' => 'Configured Nests',
        'import_egg' => 'Import Egg',
        'create_new' => 'Create New',
        'id' => 'ID', 
        'name' => 'Name',
        'description' => 'Description',
        'eggs' => 'Eggs',
        'packs' => 'Packs',
        'servers' => 'Servers',
        'import_an_egg' => 'Import an Egg',
        'egg_file' => 'Egg File',
        'egg_file_description' => 'Select the <code>.json</code> file for the new egg that you wish to import.',
        'associated_nest' => 'Associated Nest ',
        'associated_nest_hint' => 'Select the nest that this egg will be associated with from the dropdown. If you wish to associate it with a new nest you will need to create that nest before continuing.',
        'cancel' => 'Cancel',
        'import' => 'Import',

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
    'new' => [
        'header' => [
            'title' => 'New Nest',
            'overview' => 'New Nest<small>Configure a new nest to deploy to all nodes.</small>',
            'admin' => 'Admin',
            'nests' => 'Nests',
            'new' => 'New'
        ],
        'content' => [
            'new_nest' => 'New Nest',
            'name_description' => '<small>This should be a descriptive category name that encompasses all of the eggs within the nest.</small>',
            'description' => 'Description',
            'save' => 'Save',
        ],
        'view' => [
            'name_description' => '<small>This should be a descriptive category name that encompasses all of the options within the service.</small>',
            'nest_id' => 'Nest ID',
            'nest_id_description' => 'A unique ID used for identification of this nest internally and through the API.',
            'author' => 'Author',
            'author_description' => 'The author of this service option. Please direct questions and issues to them unless this is an official option authored by <code>support@pterodactyl.io</code>.',
            'uuid' => 'UUID',
            'uuid_description' => 'A UUID that all servers using this option are assigned for identification purposes.',
            'nest_eggs' => 'Nest Eggs',
            'new_egg' => 'New Egg',
        ],
    ],
];
