<?php

return [
    'daemon_connection_failed' => 'Er was een fout tijdens een poging om met de daemon te communiceren, wat resulteerde in een HTTP/:code response code. Deze fout is gelogt.',
    'node' => [
        'servers_attached' => 'Er mogen geen servers gelinkt zijn aan de node om deze te kunnen verwijderen',
        'daemon_off_config_updated' => 'De daemon confiuratie <strong>is geupdate</strong>, er is echter een fout opgetreden tijdens het automatisch updaten van de config.yml. Je dient de configuratie (config.yml) handmatig aan te passen om de instellingen toe te passen.',
    ],
    'allocations' => [
        'server_using' => 'Er is momenteel een server gekoppeld aan deze allocation. Je kunt de allocation enkel verwijderen als er geen servers aan verbonden zijn.',
        'too_many_ports' => 'Het toevoegen van meer dan 1000 porten in een single rage wordt niet ondersteund.',
        'invalid_mapping' => 'De opgegeven mapping voor :port was ongeldig en kon niet worden verwerkt.',
        'cidr_out_of_range' => 'CIDR notation staat alleen masks tussen /25 en /32 toe.',
        'port_out_of_range' => 'Ports in een allocation dienen groter dan 1024 en lager of gelijk aan 65535 te zijn.',
    ],
    'nest' => [
        'delete_has_servers' => 'Een nest met actieve servers kan niet worden verwijderd van het paneel.',
        'egg' => [
            'delete_has_servers' => 'Een Egg met actieve servers kan niet worden verwijderd van het paneel.',
            'invalid_copy_id' => 'De geselecteerde Egg om het script te kopieren bestaat niet, of kopieert zelf al een script.',
            'must_be_child' => 'De "Copy Settings From" directive voor deze egg dient een child optie te zijn voor de geselecteerde Nest.',
            'has_children' => 'Deze Egg is een parent aan een of meer andere Eggs. Gelieve eerst die Eggs te verwijderen, voordat je deze verwijderd.',
        ],
        'variables' => [
            'env_not_unique' => 'De environment variable variable :name dient uniek te zijn voor deze Egg.',
            'reserved_name' => 'De environment variable :name is beschermd en kan niet worden toegewezen aan een variable.',
            'bad_validation_rule' => 'De validatie regel ":rule" is niet geldig voor deze applicatie.',
        ],
        'importer' => [
            'json_error' => 'Er was een fout tijdens de parse van het JSON bestand: :error.',
            'file_error' => 'Het opgegeven JSON bestand is ongeldig.',
            'invalid_json_provided' => 'Het opegeven JSON bestand bestaat uit een niet geldig formaat en kan niet worden herkend.',
        ],
    ],
    'subusers' => [
        'editing_self' => 'Je eigen mede-gebruiker aanpassen is niet toegestaan.',
        'user_is_owner' => 'Je kunt de servereigenaar niet toevoegen als mede-gebruiker.',
        'subuser_exists' => 'De gebruiker met dit e-mailadres is al toegewezen als mede-gebruiker op deze server.',
    ],
    'databases' => [
        'delete_has_databases' => 'Je kan geen database-hos met actieve databases verwijderen.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'De maximale interval tijd voor een chained task is 15 minuten.',
    ],
    'locations' => [
        'has_nodes' => 'Je kunt geen locatie met actieve nodes verwijderen.',
    ],
    'users' => [
        'node_revocation_failed' => 'Kan de keys van <a href=":link">Node #:node</a>. :error niet intrekken',
    ],
    'deployment' => [
        'no_viable_nodes' => 'Er zijn geen nodes gevonden welke voldoen aan de eisen voor automatische toewijzing.',
        'no_viable_allocations' => 'Er zijn geen allocations gevonden die voldoen aan de vereisten voor automatische toewijzing.',
    ],
    'api' => [
        'resource_not_found' => 'De opgevraagde resource bestaat niet op de server',
    ],
];
