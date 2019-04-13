<?php

return [
    'notices' => [
        'created' => 'Een nieuwe nest, :name, is succesvol aangemaakt.',
        'deleted' => 'De nest is succesvol verwijderd uit het Paneel.',
        'updated' => 'De configuratie opties voor de nest zijn succesvol bijgewerkt.',
    ],
    'eggs' => [
        'notices' => [
            'imported' => 'De Egg en de bijbehorden variabelen zijn succesvol geïmporteerd.',
            'updated_via_import' => 'Deze Egg is bijgewerkt met behulp van het geleverde bestand.',
            'deleted' => 'De Egg is succesvol van het Paneel verwijdert.',
            'updated' => 'De Egg configuratie is succesvol bijgewerkt.',
            'script_updated' => 'Het installatiescript van de Egg is bijgewerkt en wordt uitgevoerd wanneer servers zijn geïnstalleerd.',
            'egg_created' => 'Een niewe Egg is succesvol aangemaakt. U moet alle draaiende daemons opnieuw opstarten om deze Egg toe te passen.',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => 'De variabele ":variable" is verwijdert en is niet meer beschikbaar voor server die opnieuw worden opgebouwd.',
            'variable_updated' => 'De variabele ":variable" is bijgewerkt. U moet alle server die deze variabele gebruiken opnieuw opbouwen om de wijzigingen toe te passen.',
            'variable_created' => 'De nieuwe variabele is met succes aangemaakt en toegewijzen aan deze Egg.',
        ],
    ],
];
