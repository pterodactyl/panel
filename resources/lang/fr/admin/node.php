<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'validation' => [
        'fqdn_not_resolvable' => 'Le nom de domaine complet ou l\'adresse IP fournie ne correspond pas à une adresse IP valide.',
        'fqdn_required_for_ssl' => 'Un nom de domaine pleinement qualifié qui résout en une adresse IP publique est nécessaire pour utiliser le SSL pour ce node.',
    ],
    'notices' => [
        'allocations_added' => 'Les allocations ont été ajoutées avec succès à ce node.',
        'node_deleted' => 'Le node a été supprimé avec succès du panel.',
        'location_required' => 'Vous devez avoir configuré au moins un emplacement avant de pouvoir ajouter un node à ce panel.',
        'node_created' => 'Nouveau node créé avec succès. Vous pouvez configurer automatiquement le démon sur cette machine en visitant l\'onglet \'Configuration\'. <strong>Avant de pouvoir ajouter des serveurs, vous devez d\'abord allouer au moins une adresse IP et un port. </strong>',
        'node_updated' => 'Les informations de node ont été mises à jour. Si des paramètres de démon ont été modifiés, vous devrez le redémarrer pour que ces modifications prennent effet.',
        'unallocated_deleted' => 'Suppresion de tous les ports non alloués pour l\'adresse <code>:ip</code>.',
    ],
];



