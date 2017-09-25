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
        'no_new_default_allocation' => 'You are attempting to delete the default allocation for this server but there is no fallback allocation to use.',
        'marked_as_failed' => 'This server was marked as having failed a previous installation. Current status cannot be toggled in this state.',
        'bad_variable' => 'There was a validation error with the :name variable.',
        'daemon_exception' => 'There was an exception while attempting to communicate with the daemon resulting in a HTTP/:code response code. This exception has been logged.',
        'default_allocation_not_found' => 'The requested default allocation was not found in this server\'s allocations.',
    ],
    'alerts' => [
        'startup_changed' => 'Se ha actualizado la configuración de inicio de este servidor. Si se ha cambiado el servicio o la opción de este servidor una reinstalación será ocurriendo ahora.',
        'server_deleted' => 'Se ha eliminado correctamente el servidor.',
        'server_created' => 'El servidor se creó correctamente en el panel. Por favor, permite que el demonio de unos pocos minutos para instalar por completo este servidor.',
        'build_updated' => 'Los detalles de la compilación para este servidor se han actualizado. Algunos cambios pueden requerir un reinicio para tener efecto.',
        'suspension_toggled' => 'El estado de la suspensión del servidor se ha cambiado a :status.',
        'rebuild_on_boot' => 'Este servidor se ha marcado como que requiere una reconstrucción de Contenedor Docker. El servidor se reconstruirá la próxima vez que se inicie.',
        'install_toggled' => 'Se ha cambiado el estado de la instalación de este servidor.',
        'server_reinstalled' => 'Este servidor se ha puesto en cola para una reinstalación que comenzar ahora.',
        'details_updated' => 'Los detalles del servidor se han actualizado correctamente.',
        'docker_image_updated' => 'Cambió correctamente la imagen predeterminada de Docker para utilizarla en este servidor. Se requiere un reinicio para aplicar este cambio.',
        'node_required' => 'Debe tener al menos un nodo configurado antes de poder agregar un servidor a este panel.',
    ],
];
