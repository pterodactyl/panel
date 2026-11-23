<?php

declare(strict_types=1);

return [
    'greeting' => 'Hello!',
    'greeting_error' => 'Whoops!',
    'salutation' => 'Regards,',
    'action_help' => 'If you’re having trouble clicking the ":action" button, copy and paste the URL below into your web browser:',
    'copyright' => 'All rights reserved.',
    'account_created' => [
        'subject' => 'Account Created',
        'greeting' => 'Hello :name!',
        'intro' => 'You are receiving this email because an account has been created for you on :app.',
        'username' => 'Username: :username',
        'email' => 'Email: :email',
        'action' => 'Setup Your Account',
    ],
    'added_to_server' => [
        'subject' => 'Added To Server',
        'greeting' => 'Hello :name!',
        'intro' => 'You have been added as a subuser for the following server, allowing you certain control over the server.',
        'server' => 'Server Name: :server',
        'action' => 'Visit Server',
    ],
    'removed_from_server' => [
        'subject' => 'Removed From Server',
        'greeting' => 'Hello :name.',
        'intro' => 'You have been removed as a subuser for the following server.',
        'server' => 'Server Name: :server',
        'action' => 'Visit Panel',
    ],
    'mail_tested' => [
        'subject' => 'Pterodactyl Test Message',
        'greeting' => 'Hello :name!',
        'intro' => "This is a test of the Pterodactyl mail system. You're good to go!",
    ],
    'password_reset' => [
        'subject' => 'Reset Password',
        'intro' => 'You are receiving this email because we received a password reset request for your account.',
        'action' => 'Reset Password',
        'outro' => 'If you did not request a password reset, no further action is required.',
    ],
    'server_installed' => [
        'subject' => 'Server Installed',
        'greeting' => 'Hello :name.',
        'intro' => 'Your server has finished installing and is now ready for you to use.',
        'server' => 'Server Name: :server',
        'action' => 'Login and Begin Using',
    ],
];
