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
        'fqdn_not_resolvable' => 'El FQDN o la dirección IP proporcionada no se resuelve a una dirección IP válida.',
        'fqdn_required_for_ssl' => 'Se requiere de la onu FQDN que resuelva una dirección IP pública para poder usar SSL para este nodo.',
    ],
    'notices' => [
        'allocations_added' => 'Las asignaciones se han agregado con éxito un este nodo.',
        'node_deleted' => 'Nodo se ha eliminado con éxito desde el panel de.',
        'location_required' => 'Necesita al menos una ubicación configurada antes de poder agregar la onu un nodo este panel.',
        'node_created' => 'Creado con éxito nuevo nodo. Puede configurar automáticamente el daemon en esta máquina visitando la pestaña \'Configuración\'. <strong>Antes de que usted puede agregar cualquier cantidad de servidores primero debe asignar al menos una dirección IP y el puerto.</strong>',
        'node_updated' => 'La información del nodo se ha actualizado. Si se ha cambiado la configuración del demonio, deberá reiniciarlo para que los cambios surtan efecto.',
        'unallocated_deleted' => 'Se eliminaron todos los puertos asignados para <code>:ip</code>.',
    ],
];
