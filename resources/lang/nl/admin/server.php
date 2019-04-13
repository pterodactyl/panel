<?php

return [
    'exceptions' => [
        'no_new_default_allocation' => 'U probeert de standaardtoewijzing voor deze server te verwijderen, maar er is geen fallback-toewijzing om te gebruiken.',
        'marked_as_failed' => 'Deze server is gemarkeerd als een mislukking van een eerdere installatie. De huidige status kan niet in deze staat worden geschakeld.',
        'bad_variable' => 'Er is een validatiefout met de :name variabele.',
        'daemon_exception' => 'Er is een probleem opgetreden tijdens een poging om met de daemon te communiceren wat resulteerde in een HTTP/:code reactie code. Dit probleem is gelogd.',
        'default_allocation_not_found' => 'De opgevraagde standaardtoewijzing is niet gevonden in de toewijzingen van deze server.',
    ],
    'alerts' => [
        'startup_changed' => 'De opstartconfiguratie voor deze server is bijgewerkt. Als de nest of egg van deze server is gewijzigd, vindt er nu een nieuwe installatie plaats.',
        'server_deleted' => 'De server is met succes uit het systeem verwijderd.',
        'server_created' => 'De server is met succes gecreëerd op het paneel. Sta de daemon een paar minuten toe om de server volledig te installeren.',
        'build_updated' => 'De bouwdetails voor deze server zijn bijgewerkt. Voor sommige wijzigingen is een herstart vereist.',
        'suspension_toggled' => 'De opschortingsstatus van de server is gewijzigd naar :status.',
        'rebuild_on_boot' => 'Deze server is gemarkeerd als het opnieuw laten opbouwen van een Docker Container. Dit gebeurt de volgende keer dat de server wordt gestart.',
        'install_toggled' => 'De installatiestatus voor deze server is omgeschakeld.',
        'server_reinstalled' => 'Deze server is in de wachtrij geplaatst voor een nieuwe installatie die nu begint.',
        'details_updated' => 'Servergegevens zijn met succes bijgewerkt.',
        'docker_image_updated' => 'De standaard Docker image die gebruikt wordt voor deze server is met succes gewijzigd. Een herstart is vereist om deze wijziging toe te passen.',
        'node_required' => 'U moet ten minste één node geconfigureerd hebben voordat u een server aan dit paneel kunt toevoegen.',
    ],
];
