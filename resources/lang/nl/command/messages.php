<?php

return [
    'environment' => [
        'oauth2' => [
            'oauth2_warning' => 'OAauth2 configuratie is een geavanceerde functie, gebruik het alleen als je weet wat je doet.',
            'clientId' => 'OAuth2 Client ID',
            'clientSecret' => 'OAuth2 Secret Key',
            'urlAuthorize' => 'OAuth2 Authorize URL',
            'urlAccessToken' => 'OAuth2 Access Token URL',
            'urlResourceOwnerDetails' => 'OAuth2 Gebruikers Info URL.',
            'ask_proxy' => 'Zou je een proxy willen gebruiken voor OAuth2?',
            'proxy' => 'Proxy URL En Poort (IP:poort).',
            'resource_keys_help' => 'Als je niet weet welke sleutels er gebruikt worden door je OAuth2 server moet je de documentatie raadplegen of het aan je provider vragen.',
            'id' => 'ID OAuth2 Resource Key',
            'username' => 'Gebruikersnaam OAuth2 Resource Key',
            'email' => 'Email OAuth2 Resource Key',
            'ask_first_name' => 'Geeft jouw OAuth2 server de voornaam van de gebruiker door?',
            'first_name' => 'Voornaam Van OAuth2 Resource Key',
            'ask_last_name' => 'Geeft jouw OAuth2 server de achternaam van de gebruiker door?',
            'last_name' => 'Achternaam Van OAuth2 Resource Key',
            'create_user' => 'Would you like to only allow users with an account to login or create one if the user doesn\'t have one?',
            'create_user_options' => [
                'only_allow_login' => 'Only allow users with an existing account',
                'create' => 'Create a new user',
            ],
            'create_user_warning' => [
                'only_allow_login' => 'You will have to create users with an OAuth2 ID from the admin page or convert existing accounts.',
                'create' => 'Het gebruik van een privé OAuth2 server of app is aanbevolen omdat de functie openbaar is en iedereen zo toegang kan krijgen.',
            ],
            'update_user' => 'Wil je dat de gebruikersgegevens geüpdate worden doormiddel van de OAuth2 verbinding na elke login die hier gebruik van maakt?',
            'setup_finished' => [
                'OAuth2 is ingesteld. Als je het wil uitschakelen moet je \'OAUTH2_CLIENT_ID\' weghalen uit je ENV bestand.',
                'Als je de proxy (als ingesteld) wilt uitschakelen moet je \'OAUTH2_URL_PROXY_URL\' weghalen uit je ENV bestand.',
                'Als je het wil uittesten moet je uitloggen en daarna inloggen via de nieuwe OAuth2 knop.',
            ],
            'redirect_uri_warning' => 'Stel de redirect URI in naar \''.env('APP_URL') .'/auth/login/oauth2/callback'.'\' op je OAuth2 server/provider.',
            'not_official' => 'Dit is geen officiële functie, als je hulp wil moet je jer3m01#0001 contacten op Discord.',
        ],
    ],
];
