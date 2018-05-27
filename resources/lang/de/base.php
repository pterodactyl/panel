<?php

return [
    'validation_error' => 'Es gab ein Problem mit einer oder mehreren deiner Eingaben.',
    'errors' => [
        'return' => 'Gehe zurück zur voherigen Seite',
        'home' => 'Gehe zur Startseite',
        '403' => [
            'header' => 'Zugriff verweigert',
            'desc' => 'Du darfst diese Seite nicht öffnen.',
        ],
        '404' => [
            'header' => 'Datei nicht gefunden',
            'desc' => 'Es scheint als würde diese Seite nicht exsistieren.',
        ],
        'installing' => [
            'header' => 'Server installiert...',
            'desc' => 'Der Server wird derzeit noch installiert bitte versuche es später erneut.',
        ],
        'suspended' => [
            'header' => 'Server gesperrt',
            'desc' => 'Dieser Server wurde von einem Administrator gesperrt.',
        ],
    ],
    'index' => [
        'header' => 'Deine Server',
        'header_sub' => 'Server auf die du Zugriff hast.',
        'list' => 'Server Liste',
    ],
    'api' => [
        'index' => [
            'list' => 'API-Schlüssel',
            'header' => 'API Zugriff',
            'header_sub' => 'API-Zugriffsschlüssel bearbeiten.',
            'create_new' => 'Neuen API-Schlüssel erstellen',
            'keypair_created' => 'Ein API-Schlüsselpaar wurde generiert. Dein API-Geheimtoken ist <code>:token</code>. Speichere ihn an einem sicheren Ort, denn er wird nur einmal angezeigt.',
        ],
        'new' => [
            'header' => 'Neuer API-Schlüssel',
            'header_sub' => 'Erstelle einen neuen API-Schlüssel',
            'form_title' => 'Details',
            'descriptive_memo' => [
                'title' => 'Kurzbeschreibung',
                'description' => 'Gib eine Kurzbeschreibung an, wofür der Schlüssel verwendet wird.',
            ],
            'allowed_ips' => [
                'title' => 'Erlaubte IPs',
                'description' => 'Gib eine durch Zeilen getrennte der IPs an, denen es erlaubt sein soll, mit diesem Schlüssel API-Zugriff zu haben. CIDR Notation ist erlaubt. Frei lassen, um jede IP zuzulassen.',
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
        'first_name' => 'Vorname',
        'last_name' => 'Nachname',
        'update_identity' => 'Account bearbeiten',
        'username_help' => 'Dein Benutzername muss noch frei sein und aus folgenden Zeichen bestehen: :requirements.',
    ],
    'security' => [
        'session_mgmt_disabled' => 'Der Administrator hat die Verwaltungs-Funktion der aktiven Sitzungen deaktiviert.',
        'header' => 'Account Sicherheit',
        'header_sub' => 'Aktive Sitzungen und 2-Faktor-Authentifizierung kontrollieren.',
        'sessions' => 'Aktive Sitzungen',
        '2fa_header' => '2-Faktor-Authentifizierung',
        '2fa_token_help' => 'Bitte gebe den 2FA Code von deiner 2FA APP ein (Google Authenticatior, Authy, etc.).',
        'disable_2fa' => '2-Faktor-Authentifizierung deaktivieren',
        '2fa_enabled' => 'Die 2-Faktor-Authentifizierung ist aktiviert und du wirst nach einem Sicherheits code beim anmelden gefragt. Wenn du die 2-Faktor-Authentifizierung deaktivieren möchtest, einfach einen aktuellen Code unten eingeben und bestätigen.',
        '2fa_disabled' => 'Die 2-Faktor-Authentifizierung ist auf deinem Account deaktiviert! Du solltest 2FA aktivieren, um eine höhere Sicherheit für deinen Account zu haben.',
        'enable_2fa' => '2-Faktor-Authentifizierung aktivieren',
        '2fa_qr' => '2FA konfigurieren',
        '2fa_checkpoint_help' => 'Öffne deine 2FA APP und scanne diesen QR Code, oder gebe den Code darunter manuell ein. Anschließend den Code darunter eingeben.',
        '2fa_disable_error' => 'Die 2-Faktor-Authentifizierung wurde nicht aktiviert, da der Code ungültig war.',
    ],
];
