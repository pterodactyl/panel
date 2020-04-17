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
        'fqdn_not_resolvable' => 'Die Domain scheint nicht auf eine öffentliche IP zu zeigen.',
        'fqdn_required_for_ssl' => 'Zur Nutzung der SSL Funktion wird eine Domain benötigt.',
    ],
    'notices' => [
        'allocations_added' => 'IP-Zuweisungen wurden erfolgreich zu diesem Knoten hinzugefügt.',
        'node_deleted' => 'Dieser Knoten wurde erfolgreich gelöscht.',
        'location_required' => 'Du brauchst mindestens eine Standort, um einen Knoten anlegen zu können.',
        'node_created' => 'Knoten wurde erfolgreich erstellt, bitte füge die Konfigurations-Daten aus dem Konfigurations-Tab in die Datei <strong>/srv/daemon/config/core.json</strong> ein.',
        'node_updated' => 'Knoten wurde erfolgreich bearbeitet.',
        'unallocated_deleted' => 'Alle unbenutzen Ports für die IP <code>:ip</code> gelöscht.',
    ],
];
