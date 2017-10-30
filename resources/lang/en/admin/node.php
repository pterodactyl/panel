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
        'fqdn_not_resolvable' => 'El FQDN o dirección IP proporcionada no resuelve a una dirección IP válida.',
        'fqdn_required_for_ssl' => 'Se requiere un FQDN que resuelva una dirección IP pública para poder usar SSL para este nodo.',
    ],
    'notices' => [
        'allocations_added' => 'Las asignaciones se han agregado con éxito a este nodo.',
        'node_deleted' => 'Nodo se ha eliminado con éxito desde el panel.',
        'location_required' => 'Necesita al menos una ubicación configurada antes de poder agregar un nodo a este panel.',
        'node_created' => 'Successfully created new node. You can automatically configure the daemon on this machine by visiting the \'Configuration\' tab. <strong>Before you can add any servers you must first allocate at least one IP address and port.</strong>',
        'node_updated' => 'La información del nodo se ha actualizado. Si se ha cambiado la configuración del demonio, deberá reiniciarlo para que los cambios surtan efecto.',
        'unallocated_deleted' => 'Se eliminaron todos los puertos asignados para <code>:ip</code>.',
    ],
];
