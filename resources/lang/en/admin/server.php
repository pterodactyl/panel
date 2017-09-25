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
    'exceptions' => [
        'no_new_default_allocation' => 'No puede eliminar la asignación predeterminada para este servidor porque no hay asignación de fallback para utilizar.',
        'marked_as_failed' => 'Este servidor ha fallado en una instalación anterior. El estado actual no se puede cambiar en este estado.',
        'bad_variable' => 'Hay un error de validación con la variable ":name".',
        'daemon_exception' => 'Hubo una excepción al intentar comunicarse con el daemon que resulta en un código de respuesta HTTP/:code. Se ha almacenado esta excepción.',
        'default_allocation_not_found' => 'La asignación predeterminada solicitada no se encontró en las asignaciones de este servidor.',
    ],
    'alerts' => [
        'startup_changed' => 'La configuración de inicio para este servidor se ha actualizado. Si se ha cambiado el servicio o la opción de este servidor, una reinstalación se iniciará en este momento.',
        'server_deleted' => 'El servidor se ha eliminado correctamente.',
        'server_created' => 'El servidor se creó correctamente en el panel. Por favor, permitir que el demonio de unos pocos minutos para instalar por completo este servidor.',
        'build_updated' => 'Los detalles de construcción para este servidor se han actualizado. Algunos cambios pueden requerir un reinicio para tener efecto.',
        'suspension_toggled' => 'El estado de suspensión del servidor se ha cambiado a :status.',
        'rebuild_on_boot' => 'Este servidor se ha marcado como que requiere una reconstrucción de Contenedor Docker. Esto se producirá la próxima vez que se inicie el servidor.',
        'install_toggled' => 'El estado de la instalación de este servidor se ha cambiado.',
        'server_reinstalled' => 'Este servidor se ha puesto en cola para una reinstalación que comienza ahora.',
        'details_updated' => 'Los detalles del servidor se han actualizado correctamente.',
        'docker_image_updated' => 'La imagen Docker predeterminada que se va a utilizar para este servidor se ha cambiado correctamente. Es necesario reiniciar para aplicar este cambio.',
        'node_required' => 'Necesita al menos un nodo configurado antes de poder agregar un servidor a este panel.',
    ],
];
