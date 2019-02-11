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
        'title' => 'List Users',
        'overview' => 'Users<small>All registered users on the system.</small>',
        'users' => 'Users',
    ],
    'content' => [
        'user_list' => 'User List',
        'create_new' => 'Create New',
        'id' => 'ID',
        'email' => 'Email',
        'client_name' => 'Client Name',
        'username' => 'Username',
        '2fa' => '2FA',
        'servers_owned' => 'Servers Owned',
        'servers_owned_hint' => 'Servers that this user is marked as the owner of.',
        'can_access' => 'Can Access',
        'can_access_hint' => 'Servers that this user can access because they are marked as a subuser.',
    ],
    'new' => [
        'header' => [
            'title' => 'Create User',
            'overview' => 'Create User<small>Add a new user to the system.</small>',
            'create' => 'Create',
        ],
        'content' => [
            'id' => 'Identity',
            'first_name' => 'Client First Name',
            'last_name' => 'Client Last Name',
            'default_lang' => 'The default language to use when rendering the Panel for this user.',
            'permissions' => 'Permissions',
            'admin' => 'Administrator',
            'admin_hint' => 'Setting this to ‘Yes’ gives a user full administrative access.',
            'password' => 'Password',
            'password_hint' => 'Providing a user password is optional. New user emails prompt users to create a password the first time they login. If a password is provided here you will need to find a different method of providing it to the user.',
            'generated_password' => 'Generated Password:',
        ],
    ],
    'view' => [
        'header' => [
            'title' => 'Manager User:',
        ],
        'content' => [
            'password' => 'Leave blank to keep this user’s password the same. User will not receive any notification if password is changed.',
            'ignore_error' => 'Ignore exceptions raised while revoking keys.</label>
            <p class="text-muted small">If checked, any errors thrown while revoking keys across nodes will be ignored. You should avoid this checkbox if possible as any non-revoked keys could continue to be active for up to 24 hours after this account is changed. If you are needing to revoke account permissions immediately and are facing node issues, you should check this box and then restart any nodes that failed to be updated to clear out any stored keys.</p>',
            'delete' => 'Delete User',
            'delete_hint' => 'There must be no servers associated with this account in order for it to be deleted.',
        ],
    ],
];