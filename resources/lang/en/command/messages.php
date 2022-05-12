<?php

return [
    'location' => [
        'no_location_found' => 'Could not locate a record matching the provided short code.',
        'ask_short' => 'Location Short Code',
        'ask_long' => 'Location Description',
        'created' => 'Successfully created a new location (:name) with an ID of :id.',
        'deleted' => 'Successfully deleted the requested location.',
    ],
    'user' => [
        'search_users' => 'Enter a Username, User ID, or Email Address',
        'select_search_user' => 'ID of user to delete (Enter \'0\' to re-search)',
        'deleted' => 'User successfully deleted from the Panel.',
        'confirm_delete' => 'Are you sure you want to delete this user from the Panel?',
        'no_users_found' => 'No users were found for the search term provided.',
        'multiple_found' => 'Multiple accounts were found for the user provided, unable to delete a user because of the --no-interaction flag.',
        'ask_admin' => 'Is this user an administrator?',
        'ask_email' => 'Email Address',
        'ask_username' => 'Username',
        'ask_name_first' => 'First Name',
        'ask_name_last' => 'Last Name',
        'ask_password' => 'Password',
        'ask_password_tip' => 'If you would like to create an account with a random password emailed to the user, re-run this command (CTRL+C) and pass the `--no-password` flag.',
        'ask_password_help' => 'Passwords must be at least 8 characters in length and contain at least one capital letter and number.',
        '2fa_help_text' => [
            'This command will disable 2-factor authentication for a user\'s account if it is enabled. This should only be used as an account recovery command if the user is locked out of their account.',
            'If this is not what you wanted to do, press CTRL+C to exit this process.',
        ],
        '2fa_disabled' => '2-Factor authentication has been disabled for :email.',
    ],
    'schedule' => [
        'output_line' => 'Dispatching job for first task in `:schedule` (:hash).',
    ],
    'maintenance' => [
        'deleting_service_backup' => 'Deleting service backup file :file.',
    ],
    'server' => [
        'rebuild_failed' => 'Rebuild request for ":name" (#:id) on node ":node" failed with error: :message',
        'reinstall' => [
            'failed' => 'Reinstall request for ":name" (#:id) on node ":node" failed with error: :message',
            'confirm' => 'You are about to reinstall against a group of servers. Do you wish to continue?',
        ],
        'power' => [
            'confirm' => 'You are about to perform a :action against :count servers. Do you wish to continue?',
            'action_failed' => 'Power action request for ":name" (#:id) on node ":node" failed with error: :message',
        ],
    ],
    'environment' => [
        'mail' => [
            'ask_smtp_host' => 'SMTP Host (e.g. smtp.gmail.com)',
            'ask_smtp_port' => 'SMTP Port',
            'ask_smtp_username' => 'SMTP Username',
            'ask_smtp_password' => 'SMTP Password',
            'ask_mailgun_domain' => 'Mailgun Domain',
            'ask_mailgun_endpoint' => 'Mailgun Endpoint',
            'ask_mailgun_secret' => 'Mailgun Secret',
            'ask_mandrill_secret' => 'Mandrill Secret',
            'ask_postmark_username' => 'Postmark API Key',
            'ask_driver' => 'Which driver should be used for sending emails?',
            'ask_mail_from' => 'Email address emails should originate from',
            'ask_mail_name' => 'Name that emails should appear from',
            'ask_encryption' => 'Encryption method to use',
        ],
    ],
];
