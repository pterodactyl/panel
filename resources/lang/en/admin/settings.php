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
        'title' => 'Advanced Settings',
        'overview' => 'Advanced Settings<small>Configure advanced settings for Pterodactyl.</small>',
        'settings' => 'Settings',
    ],
    'content' => [
        'recaptcha' => 'reCAPTCHA',
        'status' => 'Status',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'toggle_hint' => 'If enabled, login forms and password reset forms will do a silent captcha check and display a visible captcha if needed.',
        'site_key' => 'Site Key',
        'secret_key' => 'Secret Key',
        'secret_key_hint' => 'Used for communication between your site and Google. Be sure to keep it a secret.',
        'recaptcha_hint' => 'You are currently using reCAPTCHA keys that were shipped with this Panel. For improved security it is recommended to <a href="https://www.google.com/recaptcha/admin">generate new invisible reCAPTCHA keys</a> that tied specifically to your website.',
        'http_conn' => 'HTTP Connections',
        'conn_timeout' => 'Connection Timeout',
        'conn_timeout_hint' => 'The amount of time in seconds to wait for a connection to be opened before throwing an error.',
        'request_timeout' => 'Request Timeout',
        'request_timeout_hint' => 'The amount of time in seconds to wait for a request to be completed before throwing an error.',
        'console' => 'Console',
        'message_count' => 'Message Count',
        'message_count_hint' => 'The number of messages to be pushed to the console per frequency tick.',
        'freq_tick' => 'Frequency Tick',
        'freq_tick_hint' => 'The amount of time in milliseconds between each console message sending tick.',
        'save' => 'Save',
    ],
    'index' => [
        'header' => [
            'overview' => 'Panel Settings<small>Configure Pterodactyl to your liking.</small>',
        ],
        'content' => [
            'panel_settings' => 'Panel Settings',
            'company_name' => 'Company Name',
            'company_name_hint' => 'This is the name that is used throughout the panel and in emails sent to clients.',
            'two_factor' => 'Require 2-Factor Authentication',
            'not_required' => 'Not Required',
            'admin_only' => 'Admin Only',
            'all_users' => 'All Users',
            'two_factor_hint' => 'If enabled, any account falling into the selected grouping will be required to have 2-Factor authentication enabled to use the Panel.',
            'default_lang' => 'Default Language',
            'default_lang_hint' => 'The default language to use when rendering UI components.',
        ],
    ],
    'mail' => [
        'header' => [
            'title' => 'Mail Settings',
            'overview' => 'Mail Settings<small>Configure how Pterodactyl should handle sending emails.</small>',
        ],
        'content' => [
            'email_settings' => 'Email Settings',
            'email_settings_hint' => 'This interface is limited to instances using SMTP as the mail driver. Please either use <code>php artisan p:environment:mail</code> command to update your email settings, or set <code>MAIL_DRIVER=smtp</code> in your environment file.',
            'smtp_host' => 'SMTP Host',
            'smtp_host_hint' => 'Enter the SMTP server address that mail should be sent through.',
            'smtp_port' => 'SMTP Port',
            'smtp_port_hint' => 'Enter the SMTP server port that mail should be sent through.',
            'encrypt' => 'Encryption',
            'none' => 'None',
            'tls' => 'Transport Layer Security (TLS)',
            'ssl' => 'Secure Sockets Layer (SSL)',
            'encrypt_hint' => 'Select the type of encryption to use when sending mail.',
            'username' => 'Username',
            'username_hint' => 'The username to use when connecting to the SMTP server.',
            'password' => 'Password',
            'password_hint' => 'The password to use in conjunction with the SMTP username. Leave blank to continue using the existing password. To set the password to an empty value enter <code>!e</code> into the field.',
            'mail_from' => 'Mail From',
            'mail_from_hint' => 'Enter an email address that all outgoing emails will originate from.',
            'mail_from_name' => 'Mail From Name',
            'mail_from_name_hint' => 'The name that emails should appear to come from.',
            'test' => 'Test',
            'test_settings' => 'Test Mail Settings',
            'test_settings_text' => 'Click "Test" to begin the test.',
            'success' => 'Success',
            'success_text' => 'The test message was sent successfully.',
            'ooopsi' => 'Whoops!',
            'ooopsi_textStart' => 'An error occurred while attempting to',
            'ooopsi_textEnd' => 'mail settings:',
            'updated_text' => 'Mail settings have been updated successfully and the queue worker was restarted to apply these changes.',
        ],
    ],
];
