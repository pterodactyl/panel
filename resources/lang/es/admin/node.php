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
