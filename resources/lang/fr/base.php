<?php

return [
    'validation_error' => 'Une erreur s\'est produite avec un ou plusieurs champs dans la demande.',
    'errors' => [
        'return' => 'Retourner à la page précédente',
        'home' => 'Accueil',
        '403' => [
            'header' => 'Interdit',
            'desc' => 'Vous n\'êtes pas autorisé à accéder à cette ressource sur ce serveur.',
        ],
        '404' => [
            'header' => 'Fichier non trouvé',
            'desc' => 'Nous n\'avons pas pu localiser la ressource demandée sur le serveur.',
        ],
        'installing' => [
            'header' => 'Installation du serveur',
            'desc' => 'Le serveur demandé achève toujours le processus d\'installation. S\'il vous plaît revenez dans quelques minutes, vous devriez recevoir un e-mail dès que ce processus est terminé.',
        ],
        'suspended' => [
            'header' => 'Serveur suspendu',
            'desc' => 'Ce serveur a été suspendu et inaccessible.',
        ],
        'maintenance' => [
            'header' => 'Noeud sous maintenance',
            'title' => 'Temporairement indisponible',
            'desc' => 'Ce nœud est en cours de maintenance, donc votre serveur n\'est temporairement pas accessible.',
        ],
    ],
    'index' => [
        'header' => 'Vos serveurs',
        'header_sub' => 'Serveurs auxquels vous avez accès',
        'list' => 'Liste de serveurs',
    ],
    'api' => [
        'index' => [
            'list' => 'Vos clés',
            'header' => 'API Compte',
            'header_sub' => 'Gérez les clés d\'accès qui vous permettent d\'effectuer des actions sur le panel.',
            'create_new' => 'Créer une nouvelle clé d\'API',
            'keypair_created' => 'Une clé d\'API a été générée avec succès et est répertoriée ci-dessous.',
        ],
        'new' => [
            'header' => 'Nouvelle clé d\'API',
            'header_sub' => 'Créez une nouvelle clé d\'accès au compte.',
            'form_title' => 'Détails',
            'descriptive_memo' => [
                'title' => 'Description',
                'description' => 'Entrez une brève description de cette clé qui sera utile à titre de référence.',
            ],
            'allowed_ips' => [
                'title' => 'Adresses IP autorisées',
                'description' => 'Entrez une liste délimitée par une ligne d\'adresses IP autorisées à accéder à l\'API à l\'aide de cette clé. La notation CIDR est autorisée. Laissez vide pour autoriser toute adresse IP.',
            ],
        ],
    ],
    'account' => [
        'details_updated' => 'Vos coordonnées ont été mis à jour avec succès.',
        'invalid_password' => 'Le mot de passe fourni pour votre compte n\'était pas valide.',
        'header' => 'Votre compte',
        'header_sub' => 'Gérez les détails de votre compte',
        'update_pass' => 'Mettre à jour le mot de passe',
        'update_email' => 'Mettre a jour l\'adresse email',
        'current_password' => 'Mot de passe actuel',
        'new_password' => 'Nouveau mot de passe',
        'new_password_again' => 'Répété le nouveau mot de passe',
        'new_email' => 'Nouvelle adresse Email',
        'first_name' => 'Prénom',
        'last_name' => 'Nom',
        'update_identitity' => 'Mettre à jour l\'identité',
        'username_help' => 'Votre nom d\'utilisateur doit être unique à votre compte et ne peut contenir que les éléments suivants characters: :requirements.',
        'language' => 'Langue',
    ],
    'security' => [
        'session_mgmt_disabled' => 'Votre hôte n\'a pas activé la possibilité de gérer les sessions de compte via cette interface.',
        'header' => 'Sécurité du compte',
        'header_sub' => 'Contrôlez les sessions actives et l\'authentification 2-Factor.',
        'sessions' => 'Sessions actives',
        '2fa_header' => 'Authentification 2-Factor',
        '2fa_token_help' => 'Entrez le jeton 2FA généré par votre application (Google Authenticator, Authy, etc.).',
        'disable_2fa' => 'Désactiver l\'authentification à 2-Factor',
        '2fa_enabled' => 'L\'authentification à 2-Factor est activée sur ce compte et sera nécessaire pour se connecter au panel. Si vous souhaitez désactiver 2FA, entrez simplement un jeton valide ci-dessous et soumettez le formulaire.',
        '2fa_disabled' => 'L\'authentification à 2-Factor est désactivée sur votre compte! Vous devez activer 2FA afin d\'ajouter un niveau de protection supplémentaire sur votre compte.',
        'enable_2fa' => 'Activer l\'authentification à 2-Factor',
        '2fa_qr' => 'Configurer 2FA sur votre appareil',
        '2fa_checkpoint_help' => 'Utilisez l\'application 2FA sur votre téléphone pour prendre une photo du code QR à gauche, ou entrez manuellement le code en dessous. Une fois que vous l\'avez fait, générez un jeton et entrez-le ci-dessous.',
        '2fa_disable_error' => 'Le jeton 2FA fourni n\'était pas valide. La protection n\'a pas été désactivée pour ce compte.',
    ],
];
