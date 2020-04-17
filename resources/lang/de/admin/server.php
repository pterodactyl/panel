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
        'no_new_default_allocation' => 'Du versuchst die Standard IP-Zuweisung zu löschen, ohne dass eine Fallback IP vorhanden ist.',
        'marked_as_failed' => 'Auf dem Server ist eine vorherige Installation fehlgeschlagen. Der aktuelle Status kann in diesem Zustand nicht geändert werden.',
        'bad_variable' => 'Es gab ein Problem bei der Validierung der Variable :name.',
        'daemon_exception' => 'Bei der Kommunikation mit dem Daemon ist ein HTTP/:code Response Fehler aufgetreten. Der Fehler wurde in den Log geschrieben.',
        'default_allocation_not_found' => 'Die angeforderte Standard IP-Zuweisung wurde nicht in den Zuweisungen des Servers gefunden.',
    ],
    'alerts' => [
        'startup_changed' => 'Die Start Konfiguration dieses Servers wurde geändert. Wenn das Egg oder Nest des Servers geändert wurde, wird der Server nun neu installiert.',
        'server_deleted' => 'Der Server wurde erfolgreich vom System entfernt.',
        'server_created' => 'Der Server wurde erfolgreich im Panel erstellt. Der Dameon benötigt nun einige Minuten für die Installation des Servers.',
        'build_updated' => 'Die build details des Servers wurden aktualisiert. Einige Änderungen benötigen eventuell einen Neustart des Servers.',
        'suspension_toggled' => 'Der suspension status wurde auf :status. geändert',
        'rebuild_on_boot' => 'Dieser Server benötigt einen Docker Container rebuild. Dieser wird beim nächsten Neustart des Servers durchgeführt.',
        'install_toggled' => 'Der Installations-Status des Servers wurde geändert.',
        'server_reinstalled' => 'Der Server wurde für eine Neuinstallation markiert. Diese wird nun durchgeführt.',
        'details_updated' => 'Die Server Daten wurden geändert.',
        'docker_image_updated' => 'Das Standard Docker Image des Servers wurde erfolgreich geändert. Ein Neustart ist für die Übernahme der Änderungen erforderlich.',
        'node_required' => 'Du benötigst mindestens einen Knoten, bevor du einen Server hinzufügen kannst.',
        'transfer_nodes_required' => 'Du musst mindestens zwei Knoten konfiguriert haben, bevor du einen Server umziehen kannst.',
        'transfer_started' => 'Server Umzug wurde gestartet.',
        'transfer_not_viable' => 'Der ausgewählte Knoten ist für einen Umzug nicht verfügbar.',
    ],
];
