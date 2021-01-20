<?php

return [
    'email' => [
        'title' => 'Mettez à jour votre e-mail.',
        'updated' => 'Ton adresse Mail a bien été mis a jour.',
    ],
    'password' => [
        'title' => 'Changez votre mot de passe',
        'requirements' => 'Votre nouveau mot de passe doit avoir une longueur d’au moins 8 caractères.',
        'updated' => 'Votre mot de passe a été mis à jour.',
    ],
    'two_factor' => [
        'button' => 'Configurer l’authentification à 2 facteurs',
        'disabled' => 'Authentification à deux facteurs a été désactivée sur votre compte. Vous ne serez plus invité à fournir un jeton lors de la connexion.',
        'enabled' => 'Authentification à deux facteurs a été activée sur votre compte! Désormais, lors de la connexion, vous de serez tenu de fournir le code généré par votre appareil.',
        'invalid' => 'Le jeton fourni était invalide',
        'setup' => [
            'title' => 'Setup authentification à deux facteurs',
            'help' => 'Can\'t scanne le code? Entrez le code ci-dessous dans votre application:',
            'field' => 'Enter token',
        ],
        'désactiver' => [
            'title' => 'Désactiver l’authentification à deux facteurs',
            'field' => 'Enter token',
        ],
    ],
];
