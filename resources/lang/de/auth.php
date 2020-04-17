<?php

return [
    'sign_in' => 'Anmelden',
    'go_to_login' => 'Zum Login',
    'failed' => 'Es wurde kein Account mit diesen Zugangsdaten gefunden.',

    'forgot_password' => [
        'label' => 'Passwort vergessen?',
        'label_help' => 'Gib deine E-Mail Adresse an, um Anweisungen zum zurücksetzen deines Passworts zu erhalten.',
        'button' => 'Zugriff zurückerhalten',
    ],

    'reset_password' => [
        'button' => 'Passwort zurücksetzen und anmelden',
    ],

    'two_factor' => [
        'label' => '2FA Token',
        'label_help' => 'Dieser Account erfordert einen zweiten Faktor um fortzufahren. Bitte gib den von deinem Gerät generierten Code hier ein.',
        'checkpoint_failed' => 'Der 2FA Token war ungültig.',
    ],

    'throttle' => 'Zu viele Login Versuche. Bitte in :seconds Sekunden erneut versuchen.',
    'password_requirements' => 'Das Passwort sollte mindestens 8 Zeichen lang und einmalig für diese Seite sein.',
    '2fa_must_be_enabled' => 'Der Administrator hat die Zwei-Faktor Authentifizierung zwangsweise für deinen Account aktiviert.',
];
