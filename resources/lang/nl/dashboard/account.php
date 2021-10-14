<?php

return [
    'email' => [
        'title' => 'Update je email',
        'updated' => 'Je e-mailadres is bijgewerkt.',
    ],
    'password' => [
        'title' => 'Verander je wachtwoord',
        'requirements' => 'Je nieuwe wachtwoord dient ten minste 8 karakters lang te zijn.',
        'updated' => 'Je wachtwoord is bijgewerkt.',
    ],
    'two_factor' => [
        'button' => 'Configureer 2-Factor Authentication',
        'disabled' => '2-Factor authenticatie is uitgeschakeld voor jouw account. Je zult niet langer gevraagd worden om een token tijdens het inloggen.',
        'enabled' => '2-factor authenticatie is ingeschakeld voor jouw account. Je dient nu een token op te geven tijdens het inloggen op jouw account..',
        'invalid' => 'De opgegeven token is ongeldig.',
        'setup' => [
            'title' => 'Stel two-factor authenticatie in',
            'help' => 'Kan je de code niet scannen? Vul dan handmatig deze code in:',
            'field' => 'Vul token in',
        ],
        'disable' => [
            'title' => 'Two-factor authenticatie uitzetten',
            'field' => 'Vul token in',
        ],
    ],
];
