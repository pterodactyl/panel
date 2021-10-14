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
        'no_new_default_allocation' => 'Je probeert de standaard allocation te verwijderen, maar er is geen fallback allocation ingesteld voor deze server.',
        'marked_as_failed' => 'De installatie van de server is gefaald. De status kan hierdoor niet worden veranderd.',
        'bad_variable' => 'Er was een validatie error met de :name variable.',
        'daemon_exception' => 'Er is een fout opgetreden tijdens het communiceren met de daemon wat resulteerde in een HTTP/:code response code. Deze error is gelogt. (request id: :request_id)',
        'default_allocation_not_found' => 'De opgevraagde standaard allocation is niet gevonden voor deze server.',
    ],
    'alerts' => [
        'startup_changed' => 'De opstart-gegevens van deze server zijn gewijzigd. De nieuwe instellingen worden toegepast bij een volgende herstart.',
        'server_deleted' => 'De server is succesvol verwijderd van het systeem.',
        'server_created' => 'De server is succesvol aangemaakt. Wacht a.u.b. een aantal minuten, zodat de daemon de server kan aanmaken.',
        'build_updated' => 'De build gegevens van deze server is veranderd. Sommige aanpassingen vereisen een reboot.',
        'suspension_toggled' => 'De status van de opschorting is veranderd naar :status.',
        'rebuild_on_boot' => 'Deze server staat gepland voor een Docker rebuild. Dit zal gebeuren zodra je de server opnieuw start.',
        'install_toggled' => 'De installatie status is gewijzigd voor deze server.',
        'server_reinstalled' => 'De server zal worden geherinstalleerd.',
        'details_updated' => 'Server gegevens zijn bijgewerkt.',
        'docker_image_updated' => 'De Docker image is gewijzigd. Reboot de server om de wijzigingen toe te passen.',
        'node_required' => 'Je dient minstens één node geconfigureerd te hebben om een server aan te kunnen maken.',
        'transfer_nodes_required' => 'Je hebt minstens twee nodes nodig om servers te kunnen migreren.',
        'transfer_started' => 'Server migratie is begonnen.',
        'transfer_not_viable' => 'De geselecteerde node heeft onvoldoende opslagruimte om de server te kunnen migreren.',
    ],
];
