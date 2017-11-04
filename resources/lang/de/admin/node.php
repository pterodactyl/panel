<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 * Translated by https://github.com/DRSchlaubi/
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */
return [
    'validation' => [
        'fqdn_not_resolvable' => 'Diese FQDN scheint nicht auf eine IP weiterzuleiten.',
        'fqdn_required_for_ssl' => 'Eine Domain wird für die SSL Funktion benötigt.',
    ],
    'notices' => [
        'allocations_added' => 'Allocations wurden zu diesem Node erfolgreich hinzugefügt.',
        'node_deleted' => 'Node wurde erfolgreich gelöscht.',
        'location_required' => 'Du brauchst mindestens eine Location um einen Node zu konfigurieren.',
        'node_created' => 'Node erfolgreich erstellt bitte paste die Config aus dem Configuration tab in die Datei <strong>/srv/daemon/config/core.json</strong>',
        'node_updated' => 'Node erfolgreich bearbeitet',
        'unallocated_deleted' => 'Alle unbenutzen Ports für<code>:ip</code> gelöscht.',
    ],
];
