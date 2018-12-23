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
        'service_created' => 'Un nuevo nido, a :name, ha sido creado con éxito.',
        'service_deleted' => 'Se ha eliminado correctamente el nido solicitado del panel.',
        'service_updated' => 'Se actualizaron correctamente las opciones de configuración del nido.',
        'functions_updated' => 'Se ha actualizado el archivo de funciones de nido. Tendrá que reiniciar los nodos para que estos cambios se apliquen.',
    ],
    'options' => [
        'notices' => [
            'option_deleted' => 'Se ha eliminado correctamente la opción del huevo solicitada del Panel.',
            'option_updated' => 'Opción del huevo se ha actualizado correctamente.',
            'script_updated' => 'Opción del huevo de script de instalación se ha actualizado y se ejecutará cuando se instalan servidores.',
            'option_created' => 'Nueva opción del huevo se ha creado correctamente. Es necesario reiniciar los demonios ejecutándose para aplicar este nuevo huevo.',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => 'La variable ":variable" se ha eliminado y ya no estará disponible para los servidores una vez reconstruida.',
            'variable_updated' => 'Se ha actualizado la variable ":variable". Es necesario reconstruir los servidores que utilizan esta variable con el fin de aplicar los cambios.',
            'variable_created' => 'Nueva variable de éxito ha sido creado y asignado a esta opción del huevo.',
        ],
    ],
];
