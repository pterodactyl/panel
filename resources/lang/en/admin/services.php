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
    'notices' => [
        'service_created' => 'Un nuevo servicio, :name, ha sido creado con éxito.',
        'service_deleted' => 'Se ha eliminado correctamente el servicio solicitado del panel.',
        'service_updated' => 'Se actualizaron correctamente las opciones de configuración del servicio.',
        'functions_updated' => 'Se ha actualizado el archivo de funciones de servicio. Tendrá que reiniciar los nodos para que estos cambios se apliquen.',
    ],
    'options' => [
        'notices' => [
            'option_deleted' => 'Se ha eliminado correctamente la opción de servicio solicitada del Panel.',
            'option_updated' => 'Opción de servicio se ha actualizado correctamente.',
            'script_updated' => 'Opción de servicio script de instalación se ha actualizado y se ejecutará cuando se instalan servidores.',
            'option_created' => 'Nueva opción de servicio se ha creado correctamente. Es necesario reiniciar los demonios ejecutándose para aplicar este nuevo servicio.',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => 'La variable ":variable" se ha eliminado y ya no estará disponible para los servidores una vez reconstruida.',
            'variable_updated' => 'Se ha actualizado la variable ":variable". Es necesario reconstruir los servidores que utilizan esta variable con el fin de aplicar los cambios.',
            'variable_created' => 'Nueva variable de éxito ha sido creado y asignado a esta opción de servicio.',
        ],
    ],
];
