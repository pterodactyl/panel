<?php

return [
    'account' => [
        'current_password' => 'Huidig Paswoord',
        'delete_user' => 'Verwijder Gebruiker',
        'details_updated' => 'Je account details zijn succesvol veranderd',
        'email_password' => 'Email paswoord',
        'exception' => 'Er is een fout opgetreden tijdens het veranderen van je account.',
        'first_name' => 'Voornaam',
        'header' => 'Account Beheer',
        'header_sub' => 'Beheer uw account details',
        'invalid_pass' => 'Het opgegeven wachtwoord is niet geldig voor dit account.',
        'invalid_password' => 'Het gegeven wachtwoord voor je account is niet geldig.',
        'last_name' => 'Naam',
        'new_email' => 'Nieuw email adres',
        'new_password' => 'Nieuw Paswoord',
        'new_password_again' => 'Herhaal nieuw wachtwoord',
        'totp_apps' => 'U moet een TOTP ondersteunde applicatie hebben (vb Google Authenticator, DUO Mobile, Auth, Enpass) om van deze optie gebruik te kunnen maken.',
        'totp_checkpoint_help' => 'Bevestig aub uw TOTP instellingen door de QR code rechts te scannen met de authenticator applicatie op uw smartphone. Vul vervolgens de 6-delige code, die de applicatie genereerde, in onderstaand veld in. Druk op enter wanneer u klaar bent.',
        'totp_disable' => 'Schakel 2-delige authenticatie uit.',
        'totp_disable_help' => 'Om TOTP uit te schakelen op dit account moet je een geldige TOTP token geven. TOTP bescherming zal uitgeschakeld worden als de token geldig is.',
        'totp_enable' => 'Schakel Two-Factor Authentication in',
        'totp_enabled' => 'TOTP is nu ingeschakeld op dit account. Klik op de sluit knop om te beëindigen.',
        'totp_enabled_error' => 'De opgegeven TOTP token kon niet gevalideerd worden. Probeer het aub nogmaals.',
        'totp_header' => 'Twee-Factor Authenticatie',
        'totp_qr' => 'TOTP QR Code',
        'totp_token' => 'TOTP Token
',
        'update_email' => 'Update Email Adres',
        'update_identitity' => 'Update identiteit',
        'update_pass' => 'Werk wachtwoord bij',
        'update_user' => 'Update Gebruiker',
        'username_help' => 'Uw gebruikersnaam moet uniek zijn en mas enkel volgende characters bevatten: :requirements.',
    ],
    'api' => [
        'index' => [
            'create_new' => 'Creëer nieuwe API sleutel',
            'header' => 'API toegang',
            'header_sub' => 'Beheer jouw API keys.',
            'keypair_created' => 'Een API Key-Pair is gegenereerd. Jouw API secret token is <code>:token</code>. Noteer deze code, hij wordt later niet nog een keer weergegeven.',
            'list' => 'API sleutels',
        ],
        'new' => [
            'allowed_ips' => [
                'title' => 'Toegestane IPs',
            ],
            'base' => [
                'title' => 'Basis Informatie',
            ],
            'descriptive_memo' => [
                'description' => 'Voeg een korte beschrijving over waarvoor deze API key gebruikt zal worden toe.',
                'title' => 'Beschrijvende notitie',
            ],
            'form_title' => 'Details',
            'header' => 'Nieuwe API Key',
            'header_sub' => 'Maak een nieuwe API toegangs sleutel',
            'node_management' => [
                'delete' => [
                    'title' => 'Verwijder node',
                ],
                'list' => [
                    'title' => 'Toon nodes',
                ],
            ],
            'server_management' => [
                'create' => [
                    'title' => 'Maak een server',
                ],
                'delete' => [
                    'description' => 'Geeft toegang tot het verwijderen van een server',
                ],
                'list' => [
                    'title' => 'Toon servers',
                ],
                'power' => [
                    'description' => 'Geeft toegang om de aan/uit status van een server te beheren.',
                ],
                'title' => 'Server beheer',
                'unsuspend' => [
                    'title' => 'Hef schorsing op',
                ],
            ],
            'service_management' => [
                'list' => [
                    'title' => 'Toon diensten',
                ],
                'view' => [
                    'title' => 'Toon dienst',
                ],
            ],
            'user_management' => [
                'create' => [
                    'title' => 'Maak gebruiker',
                ],
                'delete' => [
                    'description' => 'Geeft toegang tot het verwijderen van een gebruiker',
                ],
                'list' => [
                    'title' => 'Toon gebruikers',
                ],
                'title' => 'Gebruikersbeheer',
                'update' => [
                    'title' => 'Werk gebruiker bij',
                ],
                'view' => [
                    'title' => 'Toon gebruiker',
                ],
            ],
        ],
    ],
    'errors' => [
        '403' => [
            'header' => 'Geen toegang',
        ],
        '404' => [
            'header' => 'Bestand niet gevonden.',
        ],
        'return' => 'Keer terug naar de vorige pagina',
    ],
    'form_error' => 'De volgende fouten werden gevonden bij het verwerken van dit verzoek.',
    'security' => [
        '2fa_header' => '2-factor authenticatie',
        '2fa_qr' => 'Configureer 2FA op uw toestel',
        'enable_2fa' => 'Zet 2-Factor  Authentication aan',
    ],
    'server_name' => 'Server naam',
    'validation_error' => 'Er is een fout opgetreden tijdens het valideren van de data die u opgegeven hebt.',
    'view_as_admin' => 'U bekijkt de server lijst als een administrator. Hierdoor zijn alle servers die geïnstalleerd zijn op het systeem zichtbaar. Alle servers waarvan u de eigenaar bent zijn gemarkeerd met een blauwe bol links van hun naam.',
];
