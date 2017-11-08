<?php

return [
    'common' => [
        'greeting' => 'Hello',
        'whoops' => 'Whoops',
        'regards' => 'Regards,',
        'rights' => 'All rights reserved.',
        'username' => 'Username: ',
        'email' => 'Email: ',
        'server_name' => 'Server Name: ',
        'subcopy1' => 'If you’re having trouble clicking the "{{ $actionText }}" button,',
        'subcopy2' => 'copy and paste the URL below into your web browser:',
    ],
    'account_created' => [
        'subject' => 'Account Created',
        'content' => 'You are recieving this email because an account has been created for you on Pterodactyl Panel.',
        'link' => 'Setup Your Account',
    ],
    'added_to_server' => [
        'subject' => 'Added to Server',
        'content' => 'You have been added as a subuser for the following server, allowing you certain control over the server.',
        'link' => 'Visit Server',
    ],
    'removed_from_server' => [
        'subject' => 'Removed from Server',
        'content' => 'You have been removed as a subuser for the following server.',
        'link' => 'Visit Panel',
    ],
    'send_password_reset' => [
        'subject' => 'Reset Password',
        'content' => 'You are receiving this email because we received a password reset request for your account.',
        'link' => 'Reset Password',
        'note' => 'If you did not request a password reset, no further action is required.',
    ],
];
