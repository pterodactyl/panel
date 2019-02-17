<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'exceptions' => [
        'user_has_servers' => 'Cannot delete a user with active servers attached to their account. Please delete their servers before continuing.',
        'empty_oauth2_id' => 'You must provide a valid OAuth2 ID to convert the user\'s account to OAuth2.',
    ],
    'notices' => [
        'account_created' => 'Account has been created successfully.',
        'account_updated' => 'Account has been successfully updated.',
    ],
    'convert_description' => 'Convert this account into an OAuth2 account or from OAuth2 into a normal account, this will change the user\'s sign in method to OAuth2 or back to username/email and password. If converting back to a normal user an email with a password link will e sent to the user.',
    'convert_to_oauth2' => 'Convert Into OAuth2 User',
    'convert_to_normal' => 'Convert Into Normal User',
];
