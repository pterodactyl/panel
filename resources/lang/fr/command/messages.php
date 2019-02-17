<?php

return [
    'environment' => [
        'oauth2' => [
            'oauth2_warning' => [
                'La configuration de OAuth2 est une fonction avancée, ne l\'utilisez que si vous savez ce que vous faites.',
                ],
            'clientId' => 'ID Du Client OAuth2',
            'clientSecret' => 'Clé Secrete OAuth2',
            'urlAuthorize' => 'URL D\'Authorisation OAuth2',
            'urlAccessToken' => 'URL Du Jeton D\'Acces OAuth2',
            'urlResourceOwnerDetails' => 'URL Des Infos D\'Utilisateurs OAuth2.',
            'ask_proxy' => 'Voulez vous utiliser un proxy pour OAuth2?',
            'proxy' => 'URL et Port Du Proxy (IP:port).',
            'resource_keys_help' => 'Si vous ne connaissez pas les clés de resource utilisées pas vôtre serveur OAuth2 verifiez leur documentation ou demandez a vôtre procureur.',
            'id' => 'Clé De Resource OAuth2 Pour L\'ID',
            'username' => 'Clé De Resource OAuth2 Pour Le Nom D\'Utilisateur',
            'email' => 'Clé De Resource OAuth2 Pour L\'Email',
            'ask_first_name' => 'Est-ce que vôtre serveur/procureur OAuth2 renseigne le prénom de l\'utilisateur?',
            'first_name' => 'Clé De Resource OAuth2 Pour Le Prénom',
            'ask_last_name' => 'Est-ce que vôtre serveur/procureur OAuth2 renseigne le nom de famille de l\'utilisateur?',
            'last_name' => 'Clé De Resource OAuth2 Pour Le Nom De Famille',
            'create_user' => 'Voulez vous authoriser uniquement les utilisateurs avec un compte d\'utiliser OAuth2 ou voulez vous leur créer un nouveau compte si il n\'en n\'ont pas?',
            'create_user_options' => [
                'only_allow_login' => 'Uniquement authoriser les utilisateurs existant.',
                'create' => 'Créer de nouveaux comptes',
            ],
            'create_user_warning' => [
                'only_allow_login' => 'Vous devrez créer les utilisateur avec un ID OAuth2 depuis la page admin ou convertir des comptes existants.',
                'create' => 'Avoir un serveur OAuth2 privé ou une application privée est recommendé car cette fonction n\'empêche personne de se connecter via OAuth2.'
            ],
            'update_user' => 'Voulez vous mettre a jour les details des utilisateurs en utilisant les resources OAuth2 a chaque fois qu\'ils se connectent?',
            'setup_finished' => [
                'OAuth2 a été configuré, si vous voulez le désactiver enlevez \'OAUTH2_CLIENT_ID\' de vôtre fichier ENV.',
                'Si vous voulez le désactiver le proxy (si configuré) enlevez \'OAUTH2_URL_PROXY_URL\' de vôtre fichier ENV.',
                'Pour tester veuillez vous déconnecter du panel et vous connectez via le nouveau bouton OAuth2.',
            ],
            'redirect_uri_warning' => 'Veuillez configurer l\'URI de redirection á \''.env('APP_URL') .'/auth/login/oauth2/callback'.'\' chez vôtre serveur/procureur OAuth2.',
            'not_official' => 'Ceci n\'est pas une fonction officielle, veuillez contacter jer3m01#0001 sur discord pour de l\'aide.',
        ],
    ],
];
