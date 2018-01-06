<?php

return [
    'validation_error' => 'Er is een fout opgetreden met één of meerdere velden in het formulier.',
    'errors' => [
        'return' => 'Ga naar de vorige pagina',
        'home' => 'Startpagina',
        '403' => [
            'header' => 'Verboden',
            'desc' => 'U hebt geen toestemming tot de opgevraagde gegevens.',
        ],
        '404' => [
            'header' => 'Pagina niet gevonden',
            'desc' => 'De pagina die u zoekt is niet beschikbaar op de server.',
        ],
        'installing' => [
            'header' => 'Bezig met het installeren van de server',
            'desc' => 'De gevraagde server is nog bezig met het voltooien van het installatieproces. Probeer het over een paar minuten opnieuw, u ontvangt een e-mail zodra dit proces is voltooid.',
        ],
        'suspended' => [
            'header' => 'Server opgeschort',
            'desc' => 'Deze server is opgeschort en is niet meer toegankelijk.',
        ],
    ],
    'index' => [
        'header' => 'Uw servers',
        'header_sub' => 'Servers waar u toegang tot heeft.',
        'list' => 'Serverlijst',
    ],
    'api' => [
        'index' => [
            'header' => 'API Toegang',
            'header_sub' => 'Beheer uw API-sleutels.',
            'list' => 'API-sleutels',
            'create_new' => 'Maak een nieuwe API-sleutel',
            'keypair_created' => 'Een API-sleutel is met succes gegenereerd and wordt hieronder vermeld.',
        ],
        'new' => [
            'header' => 'Nieuwe API-sleutel',
            'header_sub' => 'Maak een nieuwe API-sleutel aan',
            'form_title' => 'Details',
            'descriptive_memo' => [
                'title' => 'Beschrijvende memo',
                'description' => 'Geef een korte beschrijving waarvoor deze API-sleutel zal worden gebruikt.',
            ],
            'allowed_ips' => [
                'title' => 'Toegestane IP's',
                'description' => 'Voer een door regels gescheiden lijst in van IP's die toegang hebben tot de API door deze sleutel. CIDR-notatie is toegestaan. Laat dit leeg om alle IP-adressen toe te staan.',
            ],
        ],
        'permissions' => [
            'user' => [
                'server_header' => 'Gebruikersrechten',
                'server' => [
                    'list' => [
                        'title' => 'Laat servers zien',
                        'desc' => 'Staat toe om alle servers waar de gebruiker eigenaar van is of waar hij als subgebruiker toegang toe heeft te laten zien.',
                    ],
                    'view' => [
                        'title' => 'Server bekijken',
                        'desc' => 'Staat toe om de details van een server te bekijken.',
                    ],
                    'power' => [
                        'title' => 'Aan/uit',
                        'desc' => 'Staat toe om een server aan of uit te zetten.',
                    ],
                    'command' => [
                        'title' => 'Stuur commando',
                        'desc' => 'Staat toe om een commando naar een server die aan staat te sturen.',
                    ],
                ],
            ],
            'admin' => [
                'server_header' => 'Beheer van servers',
                'server' => [
                    'list' => [
                        'title' => 'Laat servers zien',
                        'desc' => 'Staat toe om alle servers over het hele systeem te zien.',
                    ],
                    'view' => [
                        'title' => 'View Server',
                        'desc' => 'Allows view of single server including service and details.',
                    ],
                    'delete' => [
                        'title' => 'Delete Server',
                        'desc' => 'Allows deletion of a server from the system.',
                    ],
                    'create' => [
                        'title' => 'Create Server',
                        'desc' => 'Allows creation of a new server on the system.',
                    ],
                    'edit-details' => [
                        'title' => 'Edit Server Details',
                        'desc' => 'Allows editing of server details such as name, owner, description, and secret key.',
                    ],
                    'edit-container' => [
                        'title' => 'Edit Server Container',
                        'desc' => 'Allows for modification of the docker container the server runs in.',
                    ],
                    'suspend' => [
                        'title' => 'Suspend Server',
                        'desc' => 'Allows for the suspension and unsuspension of a given server.',
                    ],
                    'install' => [
                        'title' => 'Toggle Install Status',
                        'desc' => '',
                    ],
                    'rebuild' => [
                        'title' => 'Rebuild Server',
                        'desc' => '',
                    ],
                    'edit-build' => [
                        'title' => 'Edit Server Build',
                        'desc' => 'Allows editing of server build setting such as CPU and memory allocations.',
                    ],
                    'edit-startup' => [
                        'title' => 'Edit Server Startup',
                        'desc' => 'Allows modification of server startup commands and parameters.',
                    ],
                ],
                'location_header' => 'Location Control',
                'location' => [
                    'list' => [
                        'title' => 'List Locations',
                        'desc' => 'Allows listing all locations and thier associated nodes.',
                    ],
                ],
                'node_header' => 'Node Control',
                'node' => [
                    'list' => [
                        'title' => 'List Nodes',
                        'desc' => 'Allows listing of all nodes currently on the system.',
                    ],
                    'view' => [
                        'title' => 'View Node',
                        'desc' => 'Allows viewing details about a specific node including active services.',
                    ],
                    'view-config' => [
                        'title' => 'View Node Configuration',
                        'desc' => 'Danger. This allows the viewing of the node configuration file used by the daemon, and exposes secret daemon tokens.',
                    ],
                    'create' => [
                        'title' => 'Create Node',
                        'desc' => 'Allows creating a new node on the system.',
                    ],
                    'delete' => [
                        'title' => 'Delete Node',
                        'desc' => 'Allows deletion of a node from the system.',
                    ],
                ],
                'user_header' => 'User Control',
                'user' => [
                    'list' => [
                        'title' => 'List Users',
                        'desc' => 'Allows listing of all users currently on the system.',
                    ],
                    'view' => [
                        'title' => 'View User',
                        'desc' => 'Allows viewing details about a specific user including active services.',
                    ],
                    'create' => [
                        'title' => 'Create User',
                        'desc' => 'Allows creating a new user on the system.',
                    ],
                    'edit' => [
                        'title' => 'Update User',
                        'desc' => 'Allows modification of user details.',
                    ],
                    'delete' => [
                        'title' => 'Delete User',
                        'desc' => 'Allows deleting a user.',
                    ],
                ],
                'service_header' => 'Service Control',
                'service' => [
                    'list' => [
                        'title' => 'List Service',
                        'desc' => 'Allows listing of all services configured on the system.',
                    ],
                    'view' => [
                        'title' => 'View Service',
                        'desc' => 'Allows listing details about each service on the system including service options and variables.',
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
        'details_updated' => 'Your account details have been successfully updated.',
        'invalid_password' => 'The password provided for your account was not valid.',
        'header' => 'Your Account',
        'header_sub' => 'Manage your account details.',
        'update_pass' => 'Update Password',
        'update_email' => 'Update Email Address',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'new_password_again' => 'Repeat New Password',
        'new_email' => 'New Email Address',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'update_identitity' => 'Update Identity',
        'username_help' => 'Your username must be unique to your account, and may only contain the following characters: :requirements.',
    ],
    'security' => [
        'session_mgmt_disabled' => 'Your host has not enabled the ability to manage account sessions via this interface.',
        'header' => 'Account Security',
        'header_sub' => 'Control active sessions and 2-Factor Authentication.',
        'sessions' => 'Active Sessions',
        '2fa_header' => '2-Factor Authentication',
        '2fa_token_help' => 'Enter the 2FA Token generated by your app (Google Authenticatior, Authy, etc.).',
        'disable_2fa' => 'Disable 2-Factor Authentication',
        '2fa_enabled' => '2-Factor Authentication is enabled on this account and will be required in order to login to the panel. If you would like to disable 2FA, simply enter a valid token below and submit the form.',
        '2fa_disabled' => '2-Factor Authentication is disabled on your account! You should enable 2FA in order to add an extra level of protection on your account.',
        'enable_2fa' => 'Enable 2-Factor Authentication',
        '2fa_qr' => 'Confgure 2FA on Your Device',
        '2fa_checkpoint_help' => 'Use the 2FA application on your phone to take a picture of the QR code to the left, or manually enter the code under it. Once you have done so, generate a token and enter it below.',
        '2fa_disable_error' => 'The 2FA token provided was not valid. Protection has not been disabled for this account.',
    ],
];
