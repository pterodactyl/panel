<?php

return [
    'validation' => [
        'fqdn_not_resolvable' => 'Diese Domain scheint nicht auf eine IP weiterzuleiten.',
        'fqdn_required_for_ssl' => 'Eine Domain wird für die SSL Funktion benötigt.',
    ],
    'notices' => [
        'allocations_added' => 'Zuweisungen wurden zu dieser Node erfolgreich hinzugefügt.',
        'node_deleted' => 'Node wurde erfolgreich gelöscht.',
        'location_required' => 'Du brauchst mindestens eine Location um eine Node zu konfigurieren.',
        'node_created' => 'Node erfolgreich erstellt, bitte füge die Konfigurations-Daten aus dem Konfigurations-Tab in die Datei <strong>/srv/daemon/config/core.json</strong>',
        'node_updated' => 'Node erfolgreich bearbeitet.',
        'unallocated_deleted' => 'Alle unbenutzen Ports für<code>:ip</code> gelöscht.',
    ],
];
