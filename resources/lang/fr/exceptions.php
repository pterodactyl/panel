<?php

return [
    'daemon_connection_failed' => 'Une exception s\'est produite lors de la tentative de communication avec le daemon, entraînant un code de réponse HTTP/:code. Cette exception a été enregistrée.',
    'node' => [
        'servers_attached' => 'Un node ne doit avoir aucun serveur lié pour être supprimé.',
        'daemon_off_config_updated' => 'La configuration du daemon <strong>a été mis à jour</strong>, cependant, une erreur s\'est produite lors de la tentative de mise à jour automatique du fichier de configuration sur le daemon. Vous devrez mettre à jour manuellement le fichier de configuration (core.json) pour que le daemon puisse appliquer ces modifications.',
    ],
    'allocations' => [
        'server_using' => 'Un serveur est actuellement affecté à cette allocation. Une allocation ne peut être supprimée que si aucun serveur n\'est actuellement affecté.',
        'too_many_ports' => 'L\'ajout de plus de 1000 ports dans une même plage à la fois n\'est pas pris en charge.',
        'invalid_mapping' => 'La cartographie prévue pour le port :port était invalide et n\'a pas pu être traité.',
        'cidr_out_of_range' => 'La notation CIDR n\'autorise que les masques entre /25 and /32.',
        'port_out_of_range' => 'Les ports d\'une allocation doivent être supérieurs à 1024 et inférieurs ou égaux à 65535.',
    ],
    'nest' => [
        'delete_has_servers' => 'Un Nests avec des serveurs actifs connectés ne peut pas être supprimé du panel.',
        'egg' => [
            'delete_has_servers' => 'Un Egg avec des serveurs actifs qui y sont attachés ne peuvent pas être supprimés du Panel.',
            'invalid_copy_id' => 'L\'Egg sélectionné pour copier un script de l\'un n\'existe pas ou copie un script de lui-même.',
            'must_be_child' => 'La directive "Copy Settings From" pour cet Egg doit être une option enfant pour le Nest sélectionné.',
            'has_children' => 'Cet Egg est un parent pour un ou plusieurs autres Egg. S\'il vous plaît supprimer ces Egg avant de supprimer cet Egg.',
        ],
        'variables' => [
            'env_not_unique' => 'La variable d\'environnement :name doit être unique à cet Egg.',
            'reserved_name' => 'La variable d\'environnement :name est protégé et ne peut pas être affecté à une variable.',
            'bad_validation_rule' => 'La règle de validation ":rule" n\'est pas une règle valide pour cette application
            .',
        ],
        'importer' => [
            'json_error' => 'Une erreur s\'est produite lors de la tentative d\'analyse du fichier JSON: :error.',
            'file_error' => 'Le fichier JSON fourni n\'était pas valide.',
            'invalid_json_provided' => 'Le fichier JSON fourni n\'est pas dans un format qui peut être reconnu.',
        ],
    ],
    'subusers' => [
        'editing_self' => 'La modification de votre propre compte de sous-utilisateur n\'est pas autorisée.',
        'user_is_owner' => 'Vous ne pouvez pas ajouter le propriétaire du serveur en tant que sous-utilisateur pour ce serveur.',
        'subuser_exists' => 'Un utilisateur avec cette adresse électronique est déjà affecté en tant que sous-utilisateur pour ce serveur.',
    ],
    'databases' => [
        'delete_has_databases' => 'Impossible de supprimer un serveur hôte de base de données sur lequel des bases de données actives sont liées.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'L\'intervalle maximum pour une tâche enchaînée est de 15 minutes.',
    ],
    'locations' => [
        'has_nodes' => 'Impossible de supprimer un emplacement auquel sont associés des nodes actifs.',
    ],
    'users' => [
        'node_revocation_failed' => 'Échec de la révocation des clés <a href=":link">Node #:node</a>. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => 'Aucun node répondant aux exigences spécifiées pour le déploiement automatique n\'a pu être trouvé.',
        'no_viable_allocations' => 'Aucune allocation répondant aux exigences de déploiement automatique n\'a été trouvée.',
    ],
    'api' => [
        'resource_not_found' => 'La ressource demandée n\'existe pas sur ce serveur.',
    ],
];
