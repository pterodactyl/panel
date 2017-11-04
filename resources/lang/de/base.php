<?php

return [
    'validation_error' => 'Es gab ein Problem mit einer oder mehreren deriner Eingaben.',
    'errors' => [
        'return' => 'Gehe zurück zu deiner voherigen Seite',
        'home' => 'Gehe zur Startseite',
        '403' => [
            'header' => 'Forbidden',
            'desc' => 'Du darfst diese Seite nicht öffnen.',
        ],
        '404' => [
            'header' => 'File Not Found',
            'desc' => 'Es scheint als würde diese Seite nicht exsistieren.',
        ],
        'installing' => [
            'header' => 'Server Installing',
            'desc' => 'Der Server wird derzeit noch installiert bitte versuche es später erneut.',
        ],
        'suspended' => [
            'header' => 'Server Suspended',
            'desc' => 'Dieser Server wurde von einem Administrator gesperrt.',
        ],
    ],
    'index' => [
        'header' => 'Deine Server',
        'header_sub' => 'Server auf die du Zugriff hast.',
        'list' => 'Server List',
    ],
    'api' => [
        'index' => [
            'header' => 'API Access (This Site is not translated because we think that the english of developers is good enough)',
            'header_sub' => 'Manage your API access keys.',
            'list' => 'API Keys',
            'create_new' => 'Create New API key',
            'keypair_created' => 'An API Key-Pair has been generated. Your API secret token is <code>:token</code>. Please take note of this key as it will not be displayed again.',
        ],
        'new' => [
            'header' => 'New API Key',
            'header_sub' => 'Create a new API access key',
            'form_title' => 'Details',
            'descriptive_memo' => [
                'title' => 'Descriptive Memo',
                'description' => 'Enter a brief description of what this API key will be used for.',
            ],
            'allowed_ips' => [
                'title' => 'Allowed IPs',
                'description' => 'Enter a line delimitated list of IPs that are allowed to access the API using this key. CIDR notation is allowed. Leave blank to allow any IP.',
            ],
        ],
        'permissions' => [
            'user' => [
                'server_header' => 'User Rechte',
                'server' => [
                    'list' => [
                        'title' => 'List Servers',
                        'desc' => 'Der user darf seine Serverliste ansehen.',
                    ],
                    'view' => [
                        'title' => 'View Server',
                        'desc' => 'Der User darf detaillierte Informationen über seine Server sehen.',
                    ],
                    'power' => [
                        'title' => 'Toggle Power',
                        'desc' => 'Der User darf den Server starten/stoppen/restartet.',
                    ],
                    'command' => [
                        'title' => 'Send Command',
                        'desc' => 'Der User hat Zugriff auf die Server Console.',
                    ],
                ],
            ],
            'admin' => [
                'server_header' => 'Server Control',
                'server' => [
                    'list' => [
                        'title' => 'List Servers',
                        'desc' => 'Der User darf alle Server dieser Instanz sehen.',
                    ],
                    'view' => [
                        'title' => 'View Server',
                        'desc' => 'Der user darf detaillierte Informationen zu allen Servern dieser Instanz sehen.',
                    ],
                    'delete' => [
                        'title' => 'Delete Server',
                        'desc' => 'Der User darf Server löschen.',
                    ],
                    'create' => [
                        'title' => 'Create Server',
                        'desc' => 'Der User darf Server erstellen.',
                    ],
                    'edit-details' => [
                        'title' => 'Edit Server Details',
                        'desc' => 'Der User darf die Server EInstellungen bearbeiten.',
                    ],
                    'edit-container' => [
                        'title' => 'Edit Server Container',
                        'desc' => 'Der User darf die Container Einstellungen des Servers verändern.',
                    ],
                    'suspend' => [
                        'title' => 'Suspend Server',
                        'desc' => 'Der User darf Server sperren.',
                    ],
                    'install' => [
                        'title' => 'Toggle Install Status',
                        'desc' => 'Der User darf den Installationstatus bearbeiten',
                    ],
                    'rebuild' => [
                        'title' => 'Rebuild Server',
                        'desc' => 'Der User darf den Server ner erstellen',
                    ],
                    'edit-build' => [
                        'title' => 'Edit Server Build',
                        'desc' => 'Der User darf Server einstellungen bearbeiten.',
                    ],
                    'edit-startup' => [
                        'title' => 'Edit Server Startup',
                        'desc' => 'Der User darf die Startparameter ändern.',
                    ],
                ],
                'location_header' => 'Location Control',
                'location' => [
                    'list' => [
                        'title' => 'List Locations',
                        'desc' => 'Der User darf alle Locations sehen.',
                    ],
                ],
                'node_header' => 'Node Control',
                'node' => [
                    'list' => [
                        'title' => 'List Nodes',
                        'desc' => 'Der User darf alle nodes sehen',
                    ],
                    'view' => [
                        'title' => 'View Node',
                        'desc' => 'Der User darf detaillierte Details eines Nodes sehen',
                    ],
                    'view-config' => [
                        'title' => 'View Node Configuration',
                        'desc' => 'Danger. Der User kann die Konfiguration eines Node sehen.',
                    ],
                    'create' => [
                        'title' => 'Create Node',
                        'desc' => 'Der User aknn ein Node erstellen.',
                    ],
                    'delete' => [
                        'title' => 'Delete Node',
                        'desc' => 'Allows User kann ein Node löschen.',
                    ],
                ],
                'user_header' => 'User Control',
                'user' => [
                    'list' => [
                        'title' => 'List Users',
                        'desc' => 'Der User kann alle User sehen.',
                    ],
                    'view' => [
                        'title' => 'View User',
                        'desc' => 'Der User kann detaillierte Informationen der User sehen.',
                    ],
                    'create' => [
                        'title' => 'Create User',
                        'desc' => 'Der User kann einen User erstellen.',
                    ],
                    'edit' => [
                        'title' => 'Update User',
                        'desc' => 'Der User kann einen User bearbeiten.',
                    ],
                    'delete' => [
                        'title' => 'Delete User',
                        'desc' => 'Der User kann einen Server löschen.',
                    ],
                ],
                'service_header' => 'Service Control',
                'service' => [
                    'list' => [
                        'title' => 'List Service',
                        'desc' => 'Der User kann alle Services sehen.',
                    ],
                    'view' => [
                        'title' => 'View Service',
                        'desc' => 'Der user kann detaillierte Informationen über einen Service sehen.',
                    ],
                ],
                'option_header' => 'Option Control',
                'option' => [
                    'list' => [
                        'title' => 'List Options',
                        'desc' => '',
                    ],
                    'view' => [
                        'title' => 'View Option',
                        'desc' => '',
                    ],
                ],
                'pack_header' => 'Pack Control',
                'pack' => [
                    'list' => [
                        'title' => 'List Packs',
                        'desc' => '',
                    ],
                    'view' => [
                        'title' => 'View Pack',
                        'desc' => '',
                    ],
                ],
            ],
        ],
    ],
    'account' => [
        'details_updated' => 'Dein Account wurde erfolgreich bearbeitet.',
        'invalid_password' => 'Das Passwort war leider ungültig.',
        'header' => 'Dein Account',
        'header_sub' => 'Account Details verwalten.',
        'update_pass' => 'Passwort ändern',
        'update_email' => 'Email ändern',
        'current_password' => 'Aktuelles Passwort',
        'new_password' => 'Neues Passwort',
        'new_password_again' => 'Neues Passwort wiederholen',
        'new_email' => 'Neue Email Adresse',
        'first_name' => 'Vornahme',
        'last_name' => 'Nachname',
        'update_identitity' => 'Account bearbeiten',
        'username_help' => 'Dein Username darf nicht bereits vergeben sein oder folgende Zeichen enthakten: :requirements.',
    ],
    'security' => [
        'session_mgmt_disabled' => 'Der Administrator hat diese Funktion deaktiviert.',
        'header' => 'Account Sicherheit',
        'header_sub' => '2-Factor-Authentification aktivieren.',
        'sessions' => 'Aktieve Sessions',
        '2fa_header' => '2-Factor Authentication',
        '2fa_token_help' => 'Bitte gebe den 2FA Code von deiner 2FA APP ein (Google Authenticatior, Authy, etc.).',
        'disable_2fa' => '2-Factor-Authentification deaktivieren',
        '2fa_enabled' => 'Die 2-Factor-Authentification ist aktiviert und du wirst nach einem Sicherheits code beim anmelden gefragt
        ',
        '2fa_disabled' => 'Die 2-Factor Authentication wurde deaktiviert',
        'enable_2fa' => '2-Factor-Authentification aktivieren.',
        '2fa_qr' => '2FA konfigurieren',
        '2fa_checkpoint_help' => 'Öffne deine 2FA APP und scanne diesen QR Code.',
        '2fa_disable_error' => 'Die 2-Factor-Authentification wurde nicht aktiviert da dein Code ungültig war.',
    ],
];
