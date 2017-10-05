<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'notices' => [
        'service_created' => 'A new service, :name, has been successfully created.',
        'service_deleted' => 'Successfully deleted the requested service from the Panel.',
        'service_updated' => 'Successfully updated the service configuration options.',
        'functions_updated' => 'The service functions file has been updated. You will need to reboot your Nodes in order for these changes to be applied.',
    ],
    'options' => [
        'notices' => [
            'option_deleted' => 'Successfully deleted the requested service option from the Panel.',
            'option_updated' => 'Service option configuration has been updated successfully.',
            'script_updated' => 'Service option install script has been updated and will run whenever servers are installed.',
            'option_created' => 'New service option was created successfully. You will need to restart any running daemons to apply this new service.',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => 'The variable ":variable" has been deleted and will no longer be available to servers once rebuilt.',
            'variable_updated' => 'The variable ":variable" has been updated. You will need to rebuild any servers using this variable in order to apply changes.',
            'variable_created' => 'New variable has successfully been created and assigned to this service option.',
        ],
    ],
];
