<?php

return [
    'daemon_connection_failed' => 'Es gab einen Fehler bei der Verbindung mit dem Daemon. Ausgabe: HTTP/:code response code.',
    'node' => [
        'servers_attached' => 'Ein Knoten darf keine Server enthalten um gelöscht zu werden',
        'daemon_off_config_updated' => 'Die Config <strong>wurde aktualisiert</strong>! Du musst allerdings die Config neu auf dem Server bearbeiten.',
    ],
    'allocations' => [
        'server_using' => 'Ein Server ist dieser IP-Zuordnung zugewiesen. Eine IP-Zuordnung kann nur gelöscht werden, wenn kein Server mehr zugewiesen ist.',
        'too_many_ports' => 'Du kannst leider nicht mehr als 1000 Ports gleichzeitig hinzufügen',
        'invalid_mapping' => 'Die Zuweisung für den Port :port war ungültig und konnte nicht verarbeitet werden.',
        'cidr_out_of_range' => 'CIDR Notation erlaubt Masken nur zwischen /25 und /32.',
        'port_out_of_range' => 'Ports in einer IP-Zuordnung müssen grösser als 1024 und kleiner oder gleich 65535 sein.',
    ],
    'nest' => [
        'delete_has_servers' => 'Ein Nest mit zugewiesenen aktiven Servern kann nicht gelöscht werden.',
        'egg' => [
            'delete_has_servers' => 'Ein Ei mit zugewiesenen aktiven Servern kann nicht gelöscht werden.',
            'invalid_copy_id' => 'Das Ei, von dem ein Skript kopiert werden sollte existiert entweder nicht, oder kopiert selbst ein Skript.',
            'must_be_child' => 'Die "Kopiere Einstellungen von" Direktive für diese Ei muss eine Kind-Option für das ausgewählte Ei sein.',
            'has_children' => 'Dieses Ei hat ein oder mehrere Kinder. Bitte lösche die Eier bevor du dieses löschen kannst.',
        ],
        'variables' => [
            'env_not_unique' => 'Die Umgebungsvariable :name muss einzigartig sein.',
            'reserved_name' => 'Die Umgebungsvariable :name ist geschützt und kann nicht zugewiesen werden.',
            'bad_validation_rule' => 'Die Validations-Regel ":rule" ist keine valide Regel für diese Anwendung.',
        ],
        'importer' => [
            'json_error' => 'Beim Parsen der JSON-Datei kam es zu einem Fehler: :error.',
            'file_error' => 'Die angegebene JSON-Datei war ungültig.',
            'invalid_json_provided' => 'Die angegebene JSON-Datei ist in einem unbekannten Format.',
        ],
    ],
    'packs' => [
        'delete_has_servers' => 'Ein Paket kann nicht gelöscht werden, wenn es von einem aktiven Server verwendet wird.',
        'update_has_servers' => 'Ein Paket kann nicht bearbeitet werden, wenn es von einem aktiven Server verwendet wird..',
        'invalid_upload' => 'Die Datei scheint ungültig zu sein.',
        'invalid_mime' => 'Die Datei hat nicht den angeforderten Dateityp: :type',
        'unreadable' => 'Das Archiv konnte nicht geöffnet werden.',
        'zip_extraction' => 'Es gab ein Problem beim Entpacken des Archivs.',
        'invalid_archive_exception' => 'Die Paket-Datei scheint keine import.json zu enthalten.',
    ],
    'subusers' => [
        'editing_self' => 'Du darfst deinen eigenen Subuser nicht bearbeiten.',
        'user_is_owner' => 'Du kannst den Serverbesitzer nicht als Subuser hinzufügen.',
        'subuser_exists' => 'Diese Email ist bereits registriert.',
    ],
    'databases' => [
        'delete_has_databases' => 'Es kann keine Datenbank gelöscht werden, die von einem aktiven Server verwendet wird.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'Die maximale Intervalldauer für eine verkettete Aufgabe ist 15 Minuten.',
    ],
    'locations' => [
        'has_nodes' => 'Es kann kein Standort gelöscht werden, der von einem Knoten verwendet wird',
    ],
    'users' => [
        'node_revocation_failed' => 'Fehler beim entfernen der Schlüssel auf <a href=":link">Knoten #:node</a>. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => 'Es wurden keine Knoten gefunden, die den Spezifikationen für das automatische Deployment entsprechen.',
        'no_viable_allocations' => 'Es wurden keine IP-Zuordnungen gefunden, die den Spezifikationen für das automatische Deployment entsprechen.',
    ],
    'api' => [
        'resource_not_found' => 'Die angeforderte Ressource existiert auf diesem Server nicht.',
    ],
];
