<?php

return [
    'email' => [
        'title' => 'E-Mail Adresse aktualisieren',
        'updated' => 'Deine E-Mail Adresse wurde aktualisiert.',
    ],
    'password' => [
        'title' => 'Passwort ändern',
        'requirements' => 'Das neue Passwort sollte mindestens 8 Zeichen lang sein.',
        'updated' => 'Das Passwort wude erfolgreich aktualisiert.',
    ],
    'two_factor' => [
        'button' => 'Zwei-Faktor Authentifizierung konfigurieren',
        'disabled' => 'Zwei-Faktor Authentifizierung wurde in deinem Account deaktiviert. Du wirst beim Login nun nich mehr nach einem Code gefragt.',
        'enabled' => 'Zwei-Faktor Authentifizierung wurde aktiviert! Ab jetzt wirst du beim Login nach einem Code gefragt.',
        'invalid' => 'Der bereitgestellte Token ist ungültig.',
        'setup' => [
            'title' => 'Zwei-Faktor Authentifizierung einrichten',
            'help' => 'Code kann nicht gescannt werden? Gib diesen Code manuell in deine Anwendung ein:',
            'field' => 'Token eingeben',
        ],
        'disable' => [
            'title' => 'Zwei-Faktor Authentifizierung deaktivieren',
            'field' => 'Token eingeben',
        ],
    ],
];
