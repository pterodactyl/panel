<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'daemon_connection_failed' => 'Hubo una excepción al intentar comunicarse con el demonio que resulta en un HTTP/:code código de respuesta. Esta excepción ha sido registrado.',
    'node' => [
        'servers_attached' => 'Un nodo no debe tener servidores vinculados a la misma, en orden a ser eliminados.',
        'daemon_off_config_updated' => 'La configuración del demonio <strong>se ha actualizado</strong>, sin embargo hubo un error al intentar actualizar automáticamente el archivo de configuración del Demonio. Usted tendrá que actualizar manualmente el archivo de configuración (core.json) para el demonio para aplicar estos cambios. El demonio respondió con un HTTP/:code código de respuesta y el error ha sido iniciado.',
    ],
    'allocations' => [
        'too_many_ports' => 'La adición de más de 1000 puertos en un único momento no es compatible. Por favor, use un rango menor.',
        'invalid_mapping' => 'La cartografía proporcionada por :port no era válido y no puede ser procesado.',
        'cidr_out_of_range' => 'La notación CIDR sólo permite máscaras entre los /25 e /32.',
    ],
    'service' => [
        'delete_has_servers' => 'Un servicio con los servidores activos conectados a no se puede eliminar desde el Panel de.',
        'options' => [
            'delete_has_servers' => 'Una opción de servicio con los servidores activos conectados a no se puede eliminar desde el Panel de.',
            'invalid_copy_id' => 'La opción de servicio seleccionado para la copia de una secuencia de comandos o bien no existe, o es copia de un mismo script.',
            'must_be_child' => 'La "Configuración de la Copia De la" directiva para que esta opción debe ser un niño opción para el servicio seleccionado.',
        ],
        'variables' => [
            'env_not_unique' => 'La variable de entorno :name debe ser único para esta opción de servicio.',
            'reserved_name' => 'La variable de entorno :name está protegido y no puede ser asignado a una variable.',
        ],
    ],
    'packs' => [
        'delete_has_servers' => 'No se puede eliminar un paquete que está conectado a los servidores activos.',
        'update_has_servers' => 'No puede modificar la opción asociada ID cuando los servidores están conectados actualmente a un pack.',
        'invalid_upload' => 'El archivo no parece ser válido.',
        'invalid_mime' => 'El archivo no cumple con los requisitos tipo :type',
        'unreadable' => 'El archivo siempre y no puede ser abierto por el servidor.',
        'zip_extraction' => 'Una excepción se encontró al intentar extraer el archivo proporcionado en el servidor.',
        'invalid_archive_exception' => 'El pack archivo siempre parece que falta un archivo necesaria.alquitrán.gz o de importación.archivo json en el directorio de base de.',
    ],
    'subusers' => [
        'editing_self' => 'La edición de su propio subuser cuenta no está permitido.',
        'user_is_owner' => 'Usted puede agregar el propietario del servidor como un subuser para este servidor.',
        'subuser_exists' => 'Un usuario con esa dirección de correo electrónico ya está asignado como subuser para este servidor.',
    ],
    'databases' => [
        'delete_has_databases' => 'No se puede eliminar una base de datos de servidor de host que tiene bases de datos activas vinculados a ella.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'El intervalo máximo de tiempo para un encadenado tarea es de 15 minutos.',
    ],
    'locations' => [
        'has_nodes' => 'No se puede eliminar una ubicación que tenga activa de los nodos conectados a él.',
    ],
];
