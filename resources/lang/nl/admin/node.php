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
        'fqdn_not_resolvable' => 'Het opgegeven FQDN of IP-adres verwijst niet naar een geldig IP-adres.',
        'fqdn_required_for_ssl' => 'Een volledig domeinnaam (FQDN) dient te worden opgegeven om SSL te activeren voor deze node.',
    ],
    'notices' => [
        'allocations_added' => 'De allocations zijn succesvol aangemaakt.',
        'node_deleted' => 'De node is verwijderd van het paneel.',
        'location_required' => 'Zorg dat je minstens 1 locatie hebt aangemaakt, voordat je een node aanmaakt.',
        'node_created' => 'De nieuwe node is aangemaakt. Je kan de daemon automatisch configureren door naar de \'Configuratie\' tab te gaan. <strong>Voordat je servers kunt aanmaken dien je minstens 1 ip-adres en port toe te voegen aan je allocations.</strong>',
        'node_updated' => 'De node gegevens zijn veranderd. Als je wijzigingen hebt aangebracht aan de daemon, dien je de daemon eerst te herstarten voordat de wijzigingen actief zijn.',
        'unallocated_deleted' => 'Alle niet gebruikte allocaties zijn verwijderd <code>:ip</code>.',
    ],
];
