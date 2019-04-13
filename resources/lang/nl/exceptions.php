<?php

return [
    'daemon_connection_failed' => 'Er is een probleem opgetreden tijdens een poging om met de daemon te communiceren wat resulteerde in een HTTP/:code reactie code. Dit probleem is gelogd.',
    'node' => [
        'servers_attached' => 'Er mogen geen server gekoppeld zijn aan een node om deze te kunnen verwijderen.',
        'daemon_off_config_updated' => 'De daemon configuratie <strong>is bijgewerkt</strong>, maar er is een fout opgetreden tijdens een poging om het configuratiebestand op de daemon automatisch bij te werken. U moet het configuratiebestand (core.json) handmatig bijwerken voor de daemon om de wijzigingen toe te passen.',
    ],
    'allocations' => [
        'server_using' => 'Een server is momenteel toegewezen aan deze toewijzing. Een toewijzing kan alleen worden verwijderd als er geen server is toegewezen.',
        'too_many_ports' => 'Het tegelijk toevoegen van meer dan 1000 poorten in één bereik wordt niet ondersteund.',
        'invalid_mapping' => 'De toewijzing voor :port was ongeldig en kon niet worden verwerkt.',
        'cidr_out_of_range' => 'CIDR notatie staat alleen masks toe tussen /25 en /32.',
        'port_out_of_range' => 'Poorten in een toewijzing moeten groter zijn dan 1024 en kleiner of gelijk aan 65535.',
    ],
    'nest' => [
        'delete_has_servers' => 'Een Nest met actieve servers gekoppeld kan niet via het paneel worden verwijderd.',
        'egg' => [
            'delete_has_servers' => 'Een Egg met actieve servers gekoppeld kan niet via het paneel worden verwijderd.',
            'invalid_copy_id' => 'De geselecteerde Egg om een script te kopiëren bestaat niet of kopieert zelf een script.',
            'must_be_child' => 'De richtlijn "Kopieer Instellingen Van" voor deze Egg moet een onderliggende optie zijn voor de geselecteerde Nest.',
            'has_children' => 'Deze Egg heeft andere onderliggende Eggs. Verwijder alstublieft die Eggs eerst voordat u deze Egg verwijdert.',
        ],
        'variables' => [
            'env_not_unique' => 'De omgevingsvariabele :name moet uniek zijn voor deze Egg.',
            'reserved_name' => 'De omgevingsvariabele :name is beveiligd en kan daarom niet toegewezen worden aan een variabele.',
            'bad_validation_rule' => 'De validatieregel ":rule" is geen geldige regel voor deze applicatie.',
        ],
        'importer' => [
            'json_error' => 'Er is een fout opgetreden tijdens een poging om het JSON bestand te ontleden: :error.',
            'file_error' => 'Het geleverde JSON bestand is ongeldig.',
            'invalid_json_provided' => 'Het geleverde JSON bestand heeft geen indeling die kon worden herkend.',
        ],
    ],
    'packs' => [
        'delete_has_servers' => 'Kan geen pakket verwijderen dat is gekoppeld aan actieve servers.',
        'update_has_servers' => 'Kan de bijbehorende optie ID niet wijzigen wanneer er momenteel servers aan een pakket gekoppeld zijn.',
        'invalid_upload' => 'Het opgegeven bestand lijk niet geldig te zijn.',
        'invalid_mime' => 'Het opgegeven bestand voldoet niet aan het vereiste type :type',
        'unreadable' => 'Het opgegeven archief kan niet worden geopend door de server.',
        'zip_extraction' => 'Er is een uitzondering opgetreden tijdens een poging om het geleverde archief uit te pakken op de server.',
        'invalid_archive_exception' => 'Het geleverde pakketarchief lijkt een vereiste archive.tar.gz of import.json bestand in de basismap te missen.',
    ],
    'subusers' => [
        'editing_self' => 'Het bewerken van uw eigen subgebruiker account is niet toegestaan.',
        'user_is_owner' => 'U kunt de servereigenaar niet toevoegen als een subgebruiker voor deze server.',
        'subuser_exists' => 'Een gebruiker met dat e-mailadres is al als subgebruiker toegevoegd aan deze server.',
    ],
    'databases' => [
        'delete_has_databases' => 'Kan geen database hostserver verwijderen waaraan actieve databases gekoppeld zijn.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'De maximale interval voor een geketende taak is 15 minuten.',
    ],
    'locations' => [
        'has_nodes' => 'Kan geen locatie verwijderen als er actieve nodes aan gekoppeld zijn.',
    ],
    'users' => [
        'node_revocation_failed' => 'Kan de sleutels niet intrekken op <a href=":link">Node #:node</a>. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => 'Er zijn geen nodes gevonden die voldoen aan de vereisten voor automatische implementatie.',
        'no_viable_allocations' => 'Er zijn geen toewijzingen gevonden die voldoen aan de vereisten voor automatische implementatie.',
    ],
    'api' => [
        'resource_not_found' => 'De opgevraagde bron bestaat niet op deze server.',
    ],
];
