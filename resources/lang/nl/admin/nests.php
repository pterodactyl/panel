<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'notices' => [
        'created' => 'De nieuwe nest, :name, is succesvol aangemaakt.',
        'deleted' => 'De opgegeven nest is succesvol verwijderd.',
        'updated' => 'De nest configuratie is succesvol aangepast.',
    ],
    'eggs' => [
        'notices' => [
            'imported' => 'De egg is succesvol geimporteerd.',
            'updated_via_import' => 'De egg is aangepast met het opgegeven bestand.',
            'deleted' => 'De egg is succesvol verwijderd van het paneel.',
            'updated' => 'De egg is succesvol aangepast.',
            'script_updated' => 'De installatiescript van de egg is aangepast en zal worden gebruikt voor nieuwe installaties.',
            'egg_created' => 'Een nieuwe egg is aangemaakt. Herstart de daemon om deze nieuwe egg te activeren.',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => ':variable is verwijderd en zal niet toegankelijk zijn voor nieuwe servers.',
            'variable_updated' => ':variable is bijgewerkt. Je dient alle servers met deze egg te rebuilden om deze aanpassingen door te voeren.',
            'variable_created' => 'Het nieuwe variabel is succesvol aangemaakt voor nieuwe servers.',
        ],
    ],
];
