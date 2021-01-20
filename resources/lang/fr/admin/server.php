<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'exceptions' => [
        'no_new_default_allocation' => 'Vous tentez de supprimer l\'allocation par défaut pour ce serveur mais il n\'y a aucune allocation de secours à utiliser.',
        'marked_as_failed' => 'Ce serveur a été marqué comme ayant échoué à une installation précédente. Le statut actuel ne peut pas être basculé dans cet état.',
        'bad_variable' => 'Il y avait une erreur de validation avec :name variable.',
        'daemon_exception' => 'Il y avait une exception lors de la tentative de communication avec le démon résultant une erreur HTTP/:code comme réponse. Cette exception a été enregistrée.',
        'default_allocation_not_found' => 'L\'allocation par défaut demandée n\'a pas été trouvée dans les allocations de ce serveur.',
    ],
    'alerts' => [
        'startup_changed' => 'La configuration de démarrage de ce serveur a été mise à jour. Si le nid ou l’œuf de ce serveur a été modifié, une réinstallation aura lieu maintenant.',
        'server_deleted' => 'Serveur a été supprimé avec succès du système',
        'server_created' => 'Serveur a été créé avec succès sur le panel. S’il vous plaît permettre au daemon quelques minutes pour installer complètement ce serveur.',
        'build_updated' => 'Les détails de construction de ce serveur ont été mis à jour. Certains changements peuvent nécessiter un redémarrage pour prendre effet.',
        'suspension_toggled' => 'Statut de suspension serveur a été changé en :status.',
        'rebuild_on_boot' => 'Ce serveur a été marqué comme nécessitant une reconstruction de conteneur Docker. Cela se produira la prochaine fois que le serveur sera démarré.',
        'install_toggled' => 'L’état d’installation de ce serveur a été basculé.',
        'server_reinstalled' => 'Ce serveur a été mis en file d’attente pour une réinstallation commençant maintenant.',
        'details_updated' => 'Les détails du serveur ont été mis à jour avec succès.',
        'docker_image_updated' => 'Modification réussie de l’image Docker par défaut à utiliser pour ce serveur. Un redémarrage est nécessaire pour appliquer ce changement.',
        'node_required' => 'Vous devez avoir configuré au moins un node (node) avant de pouvoir ajouter un serveur à ce panel.',
        'transfer_nodes_required' => 'Vous devez avoir au moins deux node configurés avant de pouvoir transférer des serveurs.',
        'transfer_started' => 'Le transfert de serveur a été commencé.',
        'transfer_not_viable' => 'Le node que vous avez sélectionné n’est pas viable pour ce transfert.',
    ],
];
