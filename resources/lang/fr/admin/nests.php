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
        'created' => 'Un nouveau nest avec le nom :name a été créé avec succès.',
        'deleted' => 'Suppression réussie du nest demandé sur le Panel.',
        'updated' => 'Mise à jour réussie des options de configuration du nest.',
    ],
    'eggs' => [
        'notices' => [
            'imported' => 'Importation avec succès de cet Egg et ses variables associées.',
            'updated_via_import' => 'Cet Egg a été mis à jour en utilisant le fichier fourni.',
            'deleted' => 'Suppression réussie de l\'egg demandé sur le panel.',
            'updated' => 'La configuration de l\'egg a été mise à jour avec succès.',
            'script_updated' => 'Le script d\'installation de l\'egg a été mis à jour et s\'exécutera dès que les serveurs seront installés.',
            'egg_created' => 'Un nouvel Egg a été pondu avec succès. Vous devrez redémarrer tous les démons en cours d\'exécution pour appliquer ce nouvel Egg.',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => 'La variable ":variable" a été supprimé et ne sera plus disponible pour les serveurs une fois reconstruit.',
            'variable_updated' => 'La variable ":variable" a été mis à jour. Vous devrez reconstruire tous les serveurs utilisant cette variable afin d\'appliquer les modifications.',
            'variable_created' => 'ne nouvelle variable a été créée et affectée à cet Egg avec succès.',
        ],
    ],
];
