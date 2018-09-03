<?php

return [
    'account' => [
        'current_password' => 'Mot de passe actuel',
        'delete_user' => 'Supprimer Utilisateur ',
        'details_updated' => 'Les détails de votre comptes ont été modifié avec succès.',
        'email_password' => 'Email Mot de passe ',
        'exception' => 'Une erreur est survenue durant la tentative de mise à jour de votre compte.',
        'first_name' => 'Prénom',
        'header' => 'Gestion du compte',
        'header_sub' => 'Gérer les détails de votre compte.',
        'invalid_pass' => "Le mot de passe fourni n'est pas valide pour ce compte. ",
        'invalid_password' => "Le mot de passe fourni n'est pas valide pour ce compte. ",
        'last_name' => 'Nom',
        'new_email' => 'Nouvel adresse e-mail',
        'new_password' => 'Nouveau mot de passe',
        'new_password_again' => 'Répéter le nouveau mot de passe',
        'totp_apps' => 'Vous devez posséder une application fournissant une authentification à deux facteurs (2FA) pour utiliser cette option. (ex : Authy, Enpass, DUO Mobile, Google Authenticator ...)',
        'totp_checkpoint_help' => "Veuillez vérifier vos paramètres 2FA en scannant le QR code sur votre droite avec votre application d'authentification, puis entrez le code à 6 chiffre qui s'affiche dans le champ ci-dessous. Appuyez sur entrée lorsque vous avez finit.",
        'totp_disable' => 'Désactiver l’authentification à deux facteurs ',
        'totp_disable_help' => "Pour désactiver l'authentification 2FA sur ce compte, vous devez fournir un token 2FA valide. Une fois ce token validé, la protection sera désactivée sur ce compte.",
        'totp_enable' => 'Activer l’authentification à deux facteurs',
        'totp_enabled' => 'Votre compte a été activé avec la vérification 2FA. Cliquez sur le bouton de fermeture pour terminer.',
        'totp_enabled_error' => "Le jeton 2FA fourni n'a pas pu être vérifié. Veuillez réessayer.",
        'totp_enable_help' => "Il semble que l'authentification à deux facteurs ne soit pas activée. Cette méthode d'authentification ajoute une barrière supplémentaire empêchant toute connexion non autorisée sur votre compte. Si vous l'activez, vous devrez entrer un code généré sur votre téléphone ou sur un autre périphérique compatible 2FA avant de terminer votre connexion.",
        'totp_header' => 'Authentification à deux facteurs',
        'totp_qr' => 'QR Code 2FA',
        'totp_token' => 'Jeton 2FA',
        'update_email' => "Modifier l'adresse e-mail",
        'update_identitity' => 'Modifier les informations',
        'update_pass' => 'Modifier le mot de passe',
        'update_user' => "Modifier l'utilisateur",
        'username_help' => "Votre nom d'utilisateur doit être unique à votre compte et ne doit être composé que des caractères suivants : :requirements.",
    ],
    'api' => [
        'index' => [
            'create_new' => 'Créer une nouvelle clé API',
            'header' => "Accès à l'API",
            'header_sub' => "Gérer vos clés d'accès à l'API.",
            'keypair_created' => "Une paire de clés API vient d'être générée. Votre jeton secret API est <code>:jeton </code>. Veuillez prendre note de cette clé car elle ne sera plus affichée.",
            'list' => 'Clés API.',
        ],
        'new' => [
            'allowed_ips' => [
                'description' => "Entrez une liste d'IP ayant accès à l'API utilisant cette clé, chacune séparées par un saut de ligne. La notation CIDR est autorisée. Laissez vide pour autoriser n'importe quelle IP.",
                'title' => 'IPs autorisées',
            ],
            'base' => [
                'information' => [
                    'description' => 'Retourne une liste de tout les serveur auquel ce compte peux accéder.',
                    'title' => 'Information de base',
                ],
                'title' => 'Information de base ',
            ],
            'descriptive_memo' => [
                'description' => "Entrez une courte description de ce pourquoi cette clef d'API sera utilisée.",
                'title' => 'Résumé',
            ],
            'form_title' => 'Détails ',
            'header' => "Nouvelle clef d'API ",
            'header_sub' => 'Crée une nouvelle clef d’accès API ',
            'location_management' => [
                'list' => [
                    'description' => 'Autoriser à lister touts les emplacements et leurs noeuds associés.',
                    'title' => 'Liste des emplacements ',
                ],
                'title' => "Gestionnaire d'emplacements ",
            ],
            'node_management' => [
                'allocations' => [
                    'description' => 'Permet de voir toutes les allocations sur le panel pour toutes les nodes.',
                    'title' => 'Liste des allocations ',
                ],
                'create' => [
                    'description' => 'Autoriser à crée un nouveau noeud sur le système.',
                    'title' => 'Crée noeud',
                ],
                'delete' => [
                    'description' => 'Autoriser à supprimer un noeud.',
                    'title' => 'Supprimer Nœud ',
                ],
                'list' => [
                    'description' => 'Autoriser à lister touts les noeuds actuellement sur le système. ',
                    'title' => 'Liste des noeuds ',
                ],
                'title' => 'Gestion du noeud ',
                'view' => [
                    'description' => "Autoriser à voir les détails à propos d'un noeud spécifique incluant des services actifs. ",
                    'title' => 'Liste de noeuds simple',
                ],
            ],
            'server_management' => [
                'build' => [
                    'title' => 'Mettre à jour la construction',
                ],
                'command' => [
                    'description' => 'Autoriser un utilisateur à envoyer une commande spécifique au serveur.',
                    'title' => 'Envoyer une commande ',
                ],
                'config' => [
                    'description' => 'Autoriser à modifier la configuration serveur (nom, propriétaire et jeton d’accès).',
                    'title' => 'Mettre à jour la configuration',
                ],
                'create' => [
                    'description' => 'Autoriser à crée un nouveau serveur sur le système. ',
                    'title' => 'Crée un serveur',
                ],
                'delete' => [
                    'description' => 'Autoriser à supprimer un serveur.',
                    'title' => 'Supprimer serveur',
                ],
                'list' => [
                    'description' => 'Autoriser à lister tout les serveur actuellement sur le système.',
                    'title' => 'Liste des serveurs ',
                ],
                'power' => [
                    'description' => "Permet de contrôler d'allumer et d’éteindre le serveur.",
                    'title' => 'Alimentation Serveur ',
                ],
                'server' => [
                    'description' => 'Permet de voir toutes les informations sur un seul serveur, incluant ses dernières statistiques et ce qui lui est alloué.',
                    'title' => 'Info Serveur ',
                ],
                'suspend' => [
                    'description' => 'Autoriser à suspendre une instance serveur ',
                    'title' => 'Serveur suspendu ',
                ],
                'title' => 'Gestionnaire serveur ',
                'unsuspend' => [
                    'description' => 'Autoriser à dé-suspendre une instance serveur ',
                    'title' => 'Serveur non suspendu ',
                ],
                'view' => [
                    'description' => "Autoriser à voir les détails à propose d'un serveur spécifique incluant le daemon_token comme information de processus actuelle
",
                    'title' => 'Montrer serveur simple ',
                ],
            ],
            'service_management' => [
                'list' => [
                    'description' => 'Autoriser à lister tout les services configuré sur le système. ',
                    'title' => 'Liste des Services ',
                ],
                'title' => 'Gestionnaire de service',
                'view' => [
                    'description' => 'Autoriser à lister les détails à propos de chaque service sur le système incluant un service avec des options et des variables.',
                    'title' => 'Liste des services uniques',
                ],
            ],
            'user_management' => [
                'create' => [
                    'description' => 'Allouer à crée une nouvel utilisateur sur le système.',
                    'title' => 'Créer utilisateur',
                ],
                'delete' => [
                    'description' => 'Autoriser à supprimer un utilisateur.',
                    'title' => 'Supprimer Utilisateur ',
                ],
                'list' => [
                    'description' => 'Autoriser à lister tout les utilisateur actuellement sur le serveur.',
                    'title' => 'Liste des utilisateurs',
                ],
                'title' => "Gestion d'Utilisateur",
                'update' => [
                    'description' => "Autoriser à modifier les détails d'un utilisateur (email, mot de passe,  informations TOTP).",
                    'title' => "Mettre à jour l'utilisateur",
                ],
                'view' => [
                    'description' => "Autoriser à voir les détails à propos d'une utilisateur spécifique incluant un service actif. ",
                    'title' => 'Lister les utilisateur uniques',
                ],
            ],
        ],
        'permissions' => [
            'admin' => [
                'location' => [
                    'list' => [
                        'desc' => 'Autoriser à lister touts les emplacements et ses noeuds associes.',
                        'title' => 'Liste des emplacements',
                    ],
                ],
                'location_header' => "Contrôles de l'emplacement",
                'node' => [
                    'create' => [
                        'desc' => 'Autoriser à crée un nouveau noeud sur le système.',
                        'title' => 'Crée un noeud ',
                    ],
                    'delete' => [
                        'desc' => 'Autoriser à supprimer un noeud sur le système.',
                        'title' => 'Supprimer Nœud ',
                    ],
                    'list' => [
                        'desc' => 'Autoriser à lister tout les noeuds actuellement sur le système.',
                        'title' => 'Liste des noeuds ',
                    ],
                    'view-config' => [
                        'desc' => 'Attention. Cette autorisation permet de voir le fichier de configuration du noeud utilisé par le daemon, et expose le jeton secret de ce dernier. ',
                        'title' => 'Voir la configuration du noeud ',
                    ],
                    'view' => [
                        'desc' => "Autoriser à voir les détails à propos d'un noeuds spécifique incluant un service actif.",
                        'title' => 'Voir Nœud ',
                    ],
                ],
                'node_header' => 'Contrôle du Nœud ',
                'option' => [
                    'list' => [
                        'title' => 'Liste des options',
                    ],
                    'view' => [
                        'title' => 'Voir les options ',
                    ],
                ],
                'option_header' => 'Options de contrôle',
                'pack' => [
                    'list' => [
                        'desc' => '',
                        'title' => 'Liste des packs ',
                    ],
                    'view' => [
                        'title' => 'Voir Pack',
                    ],
                ],
                'pack_header' => 'Gestion du Pack',
                'server' => [
                    'create' => [
                        'desc' => 'Autoriser à crée un nouveau serveur sur le système.',
                        'title' => 'Crée un Serveur ',
                    ],
                    'delete' => [
                        'desc' => 'Autoriser à supprimer un serveur du système.',
                        'title' => 'Supprimer Serveur',
                    ],
                    'edit-build' => [
                        'desc' => 'Autoriser à éditer les paramètres  de construction du serveur tel que le processeur et la mémoire. ',
                        'title' => 'Editer la construction du serveur',
                    ],
                    'edit-container' => [
                        'desc' => 'Autoriser pour les modifications du container Docker dans lequel  le serveur fonctionne.',
                        'title' => 'Editer le container du serveur',
                    ],
                    'edit-details' => [
                        'desc' => 'Autoriser à éditer les détails du serveur tels que le nom, le propriétaire, et la clef secrète. ',
                        'title' => 'Editer les détails du serveur ',
                    ],
                    'edit-startup' => [
                        'desc' => 'Autorise à modifier la commande de lancement et les paramètres.',
                        'title' => 'Editer les paramètres de démarrage du serveur',
                    ],
                    'install' => [
                        'title' => "Activer le statut de l'installation",
                    ],
                    'list' => [
                        'desc' => 'Autorise à lister tout les serveur actuellement sur le système.',
                        'title' => 'Liste des serveurs ',
                    ],
                    'rebuild' => [
                        'title' => 'Recréer un serveur',
                    ],
                    'suspend' => [
                        'desc' => 'Autorise à suspendre ou dé-suspendre un serveur donné. ',
                        'title' => 'Suspendre le serveur ',
                    ],
                    'view' => [
                        'desc' => 'Autoriser à voir un simple serveur incluant des services et des détails.',
                        'title' => 'Voir serveur ',
                    ],
                ],
                'server_header' => 'Contrôle du serveur',
                'service' => [
                    'list' => [
                        'desc' => 'Autorise à lister tout les services configurés sur le système',
                        'title' => 'Liste des services ',
                    ],
                    'view' => [
                        'desc' => 'Autoriser à lister les détails à propos de chaque service sur le système incluant des options de service et des variables',
                        'title' => 'Voir le service ',
                    ],
                ],
                'service_header' => 'Contrôle du service',
                'user' => [
                    'create' => [
                        'desc' => 'Autorise à crée un nouvel utilisateur sur le système. ',
                        'title' => 'Crée un utilisateur',
                    ],
                    'delete' => [
                        'desc' => 'Autoriser à supprimer un utilisateur ',
                        'title' => 'Supprimer utilisateur ',
                    ],
                    'edit' => [
                        'desc' => "Autoriser les modifications des détails d'un utilisateur.",
                        'title' => "Mettre à jour l'utilisateur",
                    ],
                    'list' => [
                        'desc' => 'Autoriser à lister tout les utilisateurs actuellement sur le serveur ',
                        'title' => 'Liste des utilisateur ',
                    ],
                    'view' => [
                        'desc' => "Autoriser à voir les détails à propos d'une configuration spécifique incluant des services actifs.",
                        'title' => "Voir l'utilisateurs",
                    ],
                ],
                'user_header' => "Contrôles de l'utilisateurs ",
            ],
            'user' => [
                'server' => [
                    'command' => [
                        'desc' => "Autoriser l'envoi d'une commande à un serveur en cours d'exécution.",
                        'title' => "Envoi d'une commande",
                    ],
                    'list' => [
                        'desc' => 'Autoriser à lister tous les serveurs dont un utilisateur est propriétaire ou auquel il a accès en tant que sous-utilisateur.',
                        'title' => 'Liste des serveurs ',
                    ],
                    'power' => [
                        'desc' => "Autoriser à inverser le status d'alimentation pour le serveur.",
                        'title' => 'Inverser Alimentation ',
                    ],
                    'view' => [
                        'desc' => "Autorise l'affichage d'un utilisateur spécifique du serveur.",
                        'title' => 'Voir serveur',
                    ],
                ],
                'server_header' => "Permission serveur de l'utilisateur ",
            ],
        ],
    ],
    'confirm' => 'Êtes vous sûr ? ',
    'errors' => [
        '403' => [
            'desc' => "Vous n'avez pas la permission d’accéder à cette ressource sur ce serveur.",
        ],
        '404' => [
            'header' => 'Fichier introuvable.',
        ],
    ],
    'security' => [
        '2fa_header' => 'Authentification à deux facteurs',
        'enable_2fa' => "Activer l'authentification à deux facteurs.",
        'header' => 'Sécurité du compte',
        'header_sub' => "Contrôler les sessions actives et l'authentification à 2 facteurs.",
        'sessions' => 'Sessions actives',
    ],
    'server_name' => 'Nom du Serveur',
    'validation_error' => 'Il y à une erreur avec un ou plusieurs champs dans la requête. ',
    'view_as_admin' => "Vous regardez cette liste de serveurs en tant qu'admin. En tant que tel, tous les serveurs présents sur le système seront affichés. Tous les serveurs où vous êtes indiqué comme étant le propriétaire sont marqués d'un point bleu à gauche de leur nom.",
];
