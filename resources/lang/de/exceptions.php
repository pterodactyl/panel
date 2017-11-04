<?php

return [
    'daemon_connection_failed' => 'Es gab einen Fehler bei der Verbindung mit dem Daemon. Ausgabe: HTTP/:code response code. Dieser Fehler wurde geloggt.',
    'node' => [
        'servers_attached' => 'Ein node musst Server konfiguriert haben um gelöscht zu werden.',
        'daemon_off_config_updated' => 'Die COnfiguration <strong>wurde aktualisiert</strong>! Du musst allerdings die config neu auf dem Server bearbeiten.',
    ],
    'allocations' => [
        'too_many_ports' => 'Du kannst leider nicht mehr als 1000 Ports gleichzeitig hinzufügen',
        'invalid_mapping' => 'The mapping provided for :port was invalid and could not be processed.',
        'cidr_out_of_range' => 'CIDR notation only allows masks between /25 and /32.',
    ],
    'nest' => [
        'delete_has_servers' => 'A Nest with active servers attached to it cannot be deleted from the Panel.',
        'egg' => [
            'delete_has_servers' => 'An Egg with active servers attached to it cannot be deleted from the Panel.',
            'invalid_copy_id' => 'The Egg selected for copying a script from either does not exist, or is copying a script itself.',
            'must_be_child' => 'The "Copy Settings From" directive for this Egg must be a child option for the selected Nest.',
            'has_children' => 'This Egg is a parent to one or more other Eggs. Please delete those Eggs before deleting this Egg.',
        ],
        'variables' => [
            'env_not_unique' => 'The environment variable :name must be unique to this Egg.',
            'reserved_name' => 'The environment variable :name is protected and cannot be assigned to a variable.',
        ],
        'importer' => [
            'json_error' => 'There was an error while attempting to parse the JSON file: :error.',
            'file_error' => 'The JSON file provided was not valid.',
            'invalid_json_provided' => 'The JSON file provided is not in a format that can be recognized.',
        ],
    ],
    'packs' => [
        'delete_has_servers' => 'Ein Pack kann nicht gelöscht werden wenn es von einem aktieven Server benutzt wird.',
        'update_has_servers' => 'Ein Pack kann nicht bearbeitet werden wenn es von einem aktieven Server benutzt wird..',
        'invalid_upload' => 'Die Datei scheint ungültig zu sein.',
        'invalid_mime' => 'Die Datei hat nicht den angeforderten Typ: :type',
        'unreadable' => 'Das Archiv konnte nicht geöffnet werden.',
        'zip_extraction' => 'Es gab ein Problem beim Entpacken des Archivs.',
        'invalid_archive_exception' => 'Die Pack Datei scheint keine import.json zu enthalten.',
    ],
    'subusers' => [
        'editing_self' => 'Du darfst deinen eigenen SUbuser nicht bearbeiten.',
        'user_is_owner' => 'Du kannst den Owner nicht als Subuser hinzufügen.',
        'subuser_exists' => 'Diese Email ist bereits registriert.',
    ],
    'databases' => [
        'delete_has_databases' => 'Es kann keine Datenbank gelöscht werden die von einem aktivien Server gelöscht wird.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'The maximum interval time for a chained task is 15 minutes.',
    ],
    'locations' => [
        'has_nodes' => 'Es kann keine Location gelöscht werden die von einem Node benutzt wird',
    ],
];
