<?php

return [
    'ajax' => [
        'socket_error' => 'Nous sommes incapable de nous connecter au serveur Socket.IO principal, il y a peut-être actuellement des problèmes de réseau. Le panel risque de ne pas fonctionner comme prévu.',
        'socket_status' => 'Le status du serveur a été changé par',
        'socket_status_crashed' => 'Ce serveur a été détecté comme planté.',
    ],
    'config' => [
        'allocation' => [
            'available' => 'Allocations Disponibles',
            'header' => 'Allocations du Serveur',
            'header_sub' => 'Control des IP et des ports disponible sur ce serveur.',
            'help' => "Aide d'allocation",
        ],
        'database' => [
            'add_db' => 'Ajouter une nouvelle base de donnée.',
            'header' => 'Base de données ',
            'header_sub' => 'Toutes les bases de données disponible pour ce serveur.',
            'host' => 'Hote MySQL',
            'no_dbs' => "Il n'y a pas de bases de données listées pour ce serveur.",
            'reset_password' => 'Réinitialiser le mot de passe ',
            'your_dbs' => 'Vos Bases de Données',
        ],
        'sftp' => [
            'change_pass' => 'Changer le mot de passe SFTP',
            'conn_addr' => 'Adresse de connexion ',
            'details' => 'Détails du SFTP',
            'header' => 'Configurations SFTP',
            'header_sub' => 'Informations de compte pour la connexion SFTP.',
            'oauth2_notice' => 'Le mot de passe SFTP est le <b>MOT DE PASSE DU COMPTE SUR LE PANEL</b> qui se trouve sur la page de vôtre compte et <b>non vôtre MOT DE PASSE OAUTH2</b>!',
        ],
        'startup' => [
            'command' => 'Commande de lancement ',
            'edited' => 'les variables de lancement ont bien étés édites. Elles vont prendre effet la prochaine fois que le serveur sera lancé. ',
            'edit_params' => 'Editer les paramètres ',
            'header' => 'Lancer la configuration',
            'header_sub' => 'Contrôle des arguments du lancement du serveur.',
            'startup_regex' => 'Vérification Regex',
            'startup_var' => 'Variable de commande de lancement',
            'update' => 'Mettre à jour les paramètres de lancement',
        ],
    ],
    'files' => [
        'add' => [
            'create' => 'Crée fichier ',
            'header' => 'Nouveau fichier',
            'header_sub' => 'Crée un nouveau fichier sur votre serveur.',
            'name' => 'Nom de fichier',
        ],
        'add_folder' => 'Ajouter nouveau dossier',
        'add_new' => 'Ajouter un nouveau fichier',
        'back' => 'Retourner au gestionnaire de fichiers',
        'delete' => 'Supprimer ',
        'edit' => [
            'header' => 'Editer le fichier ',
            'header_sub' => 'Faire des modification sur le fichier depuis le web.',
            'return' => 'Retourner au gestionnaire de fichiers ',
            'save' => 'Sauvegarder le fichier',
        ],
        'exceptions' => [
            'invalid_mime' => "Ce type de fichier ne peux pas être éditer via le panneau d'edition built-in.",
            'list_directory' => "Une erreur s'est produite lors de la tentative d'obtention du contenu de ce répertoire. Veuillez réessayer.",
            'max_size' => "Ce fichier est trop grand pour être édité via l'éditeur du panneau built-in. ",
        ],
        'file_name' => 'Nom de fichier',
        'header' => 'Gestionnaire de fichier',
        'header_sub' => 'Gérez tous vos fichiers directement depuis le web.',
        'last_modified' => 'Dernière modifications ',
        'loading' => 'Chargement de la structure initiale du fichier, cela peut prendre quelques secondes.',
        'mass_actions' => 'Action de masse',
        'saved' => 'Le fichier à été sauvegardé avec succès. ',
        'yaml_notice' => "Vous éditez un fichier YAML. Ces fichiers n'acceptent pas les tabulations, il faut impérativement que cela soit des espaces. Nous avons prévu le coup et avons fait en sorte qu'appuyer sur tab insérera :dropdown espaces. ",
    ],
    'index' => [
        'allocation' => 'Allocation ',
        'command' => 'Entrer une commande console',
        'cpu_use' => 'Utilisation CPU',
        'server_info' => 'Informations Serveur',
        'usage' => 'Utilisation',
    ],
    'tasks' => [
        'current' => 'Taches actuellement prévues ',
        'header_sub' => 'Automatisez votre serveur.',
        'new' => [
            'custom' => 'Valeurs custom',
            'day_of_month' => 'Jour du Mois',
            'day_of_week' => 'Jour de la Semaine',
            'tues' => 'Mardi',
        ],
    ],
    'users' => [
        'new' => [
            'create_subuser' => [
                'title' => 'Créer Sous-utilisateur',
            ],
            'edit_files' => [
                'title' => 'Editer Fichiers
',
            ],
            'email' => 'Adresse Mail',
            'list_tasks' => [
                'description' => 'Autoriser un utilisateur à lister toutes les taches (activées et désactivées) sur le serveur.',
                'title' => 'Liste des tâches',
            ],
            'move_files' => [
                'title' => 'Renommer et Déplacer Fichiers ',
            ],
            'power_kill' => [
                'description' => "Autoriser l'utilisateur à fermer le processus du serveur.",
            ],
            'power_start' => [
                'description' => "Autoriser l'utilisateur à lancer le serveur.",
            ],
            'start' => [
                'title' => 'Lancer Serveur',
            ],
            'stop' => [
                'description' => "Autoriser l'utilisateur à arrêter le serveur.",
                'title' => 'Arrêter Serveur',
            ],
            'subuser_header' => 'Gestionnaire de sous-utilisateur ',
            'upload_files' => [
                'title' => 'Envoyer Fichiers',
            ],
            'view_allocations' => [
                'title' => 'Voir les emplacements ',
            ],
            'view_databases' => [
                'title' => 'Voir le détail de la bases de données ',
            ],
            'view_schedule' => [
                'title' => 'Voir les planning',
            ],
            'view_sftp' => [
                'description' => "Autoriser l'utilisateur à voir les informations du SFTP mais pas le mot de passe.",
                'title' => 'Voir les détails du SFTP',
            ],
            'view_sftp_password' => [
                'description' => "Autoriser l'utilisateur à voir le mot de passe SFTP pour le serveur. ",
                'title' => 'Voir le mot de passe SFTP ',
            ],
            'view_startup' => [
                'description' => "Autoriser l'utilisateur à voir la commande de lancement du serveur et les variables associé. ",
                'title' => 'Voir la commande de lancement',
            ],
            'view_subuser' => [
                'description' => 'Autoriser l’utilisateur à voir les permissions assignées aux sous-utilisateurs.',
                'title' => 'Voir sous-utilisateur ',
            ],
            'view_task' => [
                'description' => "Autoriser l’utilisateur à voir les détails d'une tache spécifique.",
                'title' => 'Voir tâche',
            ],
        ],
        'update' => 'Sous-utilisateur mis à jour',
        'user_assigned' => 'Un sous-utilisateur à été assigné avec succès à ce serveur. ',
        'user_updated' => 'Permission mises à jour avec success. ',
    ],
];
