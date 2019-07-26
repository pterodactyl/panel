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
        'startup_changed' => 'La configuration de démarrage de ce serveur a été mise à jour. Si le nest ou l\'egg de ce serveur a été changé, une réinstallation aura lieu maintenant.',
        'server_deleted' => 'Le serveur a été supprimé du système avec succès.',
        'server_created' => 'Le serveur a été créé avec succès sur le panneau. Veuillez autoriser le démon quelques minutes pour installer complètement ce serveur.',
        'build_updated' => 'Les détails de construction de ce serveur ont été mis à jour. Certaines modifications peuvent nécessiter un redémarrage pour prendre effet.',
        'suspension_toggled' => 'Le statut de suspension du serveur a été modifié en :status.',
        'rebuild_on_boot' => 'Ce serveur a été marqué comme nécessitant une reconstruction: Docker Container. Cela se produira au prochain démarrage du serveur.',
        'install_toggled' => 'L\'état de l\'installation de ce serveur a été basculé.',
        'server_reinstalled' => 'Ce serveur a été mis en file d\'attente pour une réinstallation.',
        'details_updated' => 'Les détails du serveur ont été mis à jour avec succès.',
        'docker_image_updated' => 'Modification réussie de l\'image Docker par défaut à utiliser pour ce serveur. Un redémarrage est nécessaire pour appliquer cette modification.',
        'node_required' => 'Vous devez avoir configuré au moins un noeud avant de pouvoir ajouter un serveur à ce panel.',
    ],
];
