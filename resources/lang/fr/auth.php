<?php

return [
    'sign_in' => 'Connexion',
    'go_to_login' => 'Aller à Login',
    'failed' => 'Aucun compte correspondant à ces informations d’identification n’a pu être trouvé.',

    'forgot_password' => [
        'label' => 'Mot de passe oublié?',
        'label_help' => 'Entrez l’adresse e-mail de votre compte pour recevoir des instructions sur la réinitialisation de votre mot de passe.',
        'button' => 'Récupérer le compte',
    ],

    'reset_password' => [
        'button' => 'Réinitialiser et se connecter',
    ],

    'two_factor' => [
        'label' => 'l’authentification à 2 facteurs',
        'label_help' => 'Ce compte nécessite une deuxième couche d’authentification afin de continuer. Veuillez saisir le code généré par votre appareil pour compléter cette connexion.',
        'checkpoint_failed' => 'Le jeton d’authentification à deux facteurs était invalide.',
    ],

    'throttle' => 'Trop de tentatives de connexion. S’il vous plaît essayer à nouveau dans :seconds secondes.',
    'password_requirements' => 'Mot de passe doit être d’au moins 8 caractères de longueur et doit être unique à ce site.',
    '2fa_must_be_enabled' => 'L’administrateur a exigé que l’authentification à 2 facteurs soit activée pour votre compte afin d’utiliser le Panneau.',
];
