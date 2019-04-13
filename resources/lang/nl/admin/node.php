<?php

return [
    'validation' => [
        'fqdn_not_resolvable' => 'De opgegeven FGDN of IP-adres verwijst niet naar een geldig IP-adres.',
        'fqdn_required_for_ssl' => 'Een FQDN dat verwijst naar een openbaar IP-adres is vereist om SSL voor deze node te kunnen gebruiken.',
    ],
    'notices' => [
        'allocations_added' => 'De toewijzingen zijn succesvol toegevoegd aan deze node.',
        'node_deleted' => 'De node is succesvol verwijderd uit het paneel.',
        'location_required' => 'U moet ten minste één locatie geconfigureerd hebben voordat u een node kunt toevoegen aan het paneel.',
        'node_created' => 'Nieuwe node succesvolg aangemaakt. U kunt nu de daemon automatisch configureren op deze machine door naar het \'Configuratie\' tabblad te gaan. <strong>Voordat u servers kunt toevoegen moet u eerst ten minste één IP-adres en poort toewijzen.</strong>',
        'node_updated' => 'Node informatie bijgewerkt. Als er instellingen van de daemon gewijzigd zijn moet u de daemon opnieuw starten om deze wijzigingen toe te passen.',
        'unallocated_deleted' => 'Alle niet toegewezen poorten zijn verwijderd voor <code>:ip</code>.',
    ],
];
