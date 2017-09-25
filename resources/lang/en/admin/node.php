<?php
/*
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
