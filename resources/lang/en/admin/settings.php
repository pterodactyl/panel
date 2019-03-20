<?php

return [
    'oauth2' => [
        'page_title' => 'OAuth2 Settings<small>Configure the OAuth2 feature.</small>',
        'box_title' => 'OAuth2 Settings',
        'status' => [
            'label' => 'Status',
            'description' => 'If enabled, the login page will have an OAuth2 sign in button for each of the enabled providers.',
        ],
        'required' => [
            'label' => 'Require OAuth2 Authentication',
            'options' => [
                'not_required' => 'Not Required',
                'admin_only' => 'Admin Only',
                'all_users' => 'All Users',
            ],
            'description' => 'If enabled, any account falling into the selected grouping (and have at least 1 OAuth2 ID) will be required to login using one of the OAuth2 providers and default login will be disabled.',
        ],
        'providers' => [
            'label' => 'OAuth2 providers',
            'notice' => 'Please set the redirect URI as :url on your OAuth2 server/provider.',
            'create_custom_notice' => 'If you wish to create a custom provider please follow <a href=":url">this guide</a>.',
            'table_headers' => [
                'provider' => 'Provider',
                'action' => 'Action',
                'status' => 'Status',
            ],
            'create' => 'Create new',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'delete_confirmation' => [
                'title' => 'Are you sure you want to delete the provider :provider?',
                'cancel' => 'Cancel',
                'confirm' => 'Delete',
            ],
            'default_provider' => 'Default Provider',
            'default_provider_state_notice' => 'The default provider cannot be disabled',
            'default_provider_delete_notice' => 'The default provider cannot be deleted',
            'unset_provider' => 'Unset Provider',
            'unset_provider_state_notice' => 'This provider is not setup and cannot be enabled',
            'unset_provider_delete_notice' => 'This provider is not setup and cannot be deleted',
            'modal_delete_question' => 'Are you sure you want to delete the provider :provider?',
            'modal' => [
                'name' => 'Name',
                'package' => 'Package',
                'listener' => 'Listener',
                'id' => 'OAuth2 Client ID',
                'secret' => 'OAuth2 Client Secret',
                'scopes' => 'OAuth2 Scopes',
                'scopes_notice' => 'separated with \',\'',
                'widget' => 'Widget',
                'widget_html_warning' => 'This data will not be escaped! Beware of XSS',
                'widget_css_warning' => 'This data will be inside <style> tags',
                'preview' => 'Preview',
                'close' => 'Close',
                'save' => 'Apply',
                'help' => 'Check the documentation',
                'already_exists' => 'A provider with that name already exists, it must be unique.',
            ],
            'modal_create_title'=> 'Create a new provider',
            'modal_edit_title' => 'Edit provider :provider',
        ],
        'default' => [
            'label' => 'Default Provider',
            'description' => 'This will be the provider used by default and as a fallback. It cannot be disabled.',
        ],
        'save_notice' => [
            'title' => 'Please standby',
            'text' => 'Saving the providers may take a while especially if you are installing new provider packages. You may also want to <a target="_blank" href="https://pterodactyl.io/panel/troubleshooting.html#reading-error-logs">check the log</a> for errors.',
        ],
        'success_response' => 'OAuth2 settings have been updated successfully and the queue worker was restarted to apply these changes. Remember to <a target="_blank" href="https://pterodactyl.io/panel/troubleshooting.html#reading-error-logs">check the log</a> for errors if you added more providers.',
    ],
];
