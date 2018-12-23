<?php

return [
    'account' => [
        'current_password' => 'Contraseña actual',
        'delete_user' => 'Eliminar usuario',
        'details_updated' => 'Los detalles de su cuenta se han actualizado correctamente.',
        'email_password' => 'Enviar contraseña al email',
        'exception' => 'Ocurrió un error al intentar actualizar tu cuenta',
        'first_name' => 'Nombre',
        'header' => 'Tu cuenta',
        'header_sub' => 'Administra los detalles de tu cuenta.',
        'invalid_pass' => 'Esta contraseña es inválida para esta cuenta',
        'invalid_password' => 'La contraseña proporcionada por su cuenta no era válido.',
        'last_name' => 'Apellido',
        'new_email' => 'Nuevo email',
        'new_password' => 'Nueva contraseña',
        'new_password_again' => 'Repetir la nueva contraseña',
        'totp_apps' => 'Necesitas una app que soporte TOTP (p.ej. Google Authenticator, DUO Mobile, Authy, Enpass) para usar esta función.',
        'totp_checkpoint_help' => 'Por favor escanea con tu teléfono el código QR a la derecha  para verificar tu configuración TOTP, y escribe abajo el código de 6 dígitos generado. Pulsa la tecla enter cuando hayas terminado.',
        'totp_disable' => 'Desactivar la autenticación de dos pasos',
        'totp_disable_help' => 'Para desactivar la autenticación de dos pasos en esta cuenta es necesario un token de TOTP válido. Cuando sea proporcionado, se desactivará la autenticación de dos pasos.',
        'totp_enable' => 'Activar la autenticación de dos pasos',
        'totp_enabled' => 'Se ha activado la autenticación de dos pasos en tu cuenta. Pulsa "Cerrar" para terminar.',
        'totp_enabled_error' => 'El token TOTP provisto no ha podido ser verificado. Por favor, inténtalo de nuevo.',
        'totp_enable_help' => 'Parece que no tienes activada la autenticación de dos pasos. Este método añade una barrera adicional frente el uso no autorizado de tu cuenta. Tras activarlo, además de la contraseña será necesario que proveas un código que te será enviado al teléfono, email u otro medio soportado configurado en el sistema.',
        'totp_header' => 'Autenticación de dos pasos',
        'totp_qr' => 'Código QR de TOTP',
        'totp_token' => 'Token TOTP',
        'update_email' => 'Actualizar email',
        'update_identitity' => 'Actualizar identidad',
        'update_identity' => 'Actualización De La Identidad',
        'update_pass' => 'Cambiar contraseña',
        'update_user' => 'Actualizar usuario',
        'username_help' => 'Tu nombre de usuario debe ser único, y solo puede contener estos caracteres: :requirements.',
    ],
    'api' => [
        'index' => [
            'create_new' => 'Crea nueva API Key',
            'header' => 'Acceso a la API',
            'header_sub' => 'Gestiona tus acceso de API Keys',
            'keypair_created' => 'Una API Key-Pair se ha generado. Su API token secreto es <code>:token</code>. Por favor, tome nota de esta clave como no se mostrará de nuevo.',
            'list' => 'API Keys',
        ],
        'new' => [
            'allowed_ips' => [
                'description' => 'Introduzca una lista delimitada por líneas de direcciones IP a las que se les permite acceder a la API mediante esta clave. Se permite la notación CIDR. Deje en blanco para permitir cualquier IP.',
                'title' => 'Autorizar IPs',
            ],
            'base' => [
                'information' => [
                    'description' => 'Regresa a la lista de todos los servidores que esta cuenta tiene acceso.',
                    'title' => 'Información básica ',
                ],
                'title' => 'Información básica ',
            ],
            'descriptive_memo' => [
                'description' => 'Ingresa una breve descripción del propósito de uso de esta API Key.',
                'title' => 'Memo descriptivo',
            ],
            'form_title' => 'Detalles',
            'header' => 'Nueva API Key',
            'header_sub' => 'Crea un nuevo acceso de API Key',
            'location_management' => [
                'list' => [
                    'description' => 'Permite el listado de todas las locaciones y sus nodos asociados.',
                    'title' => 'Lista de locaciones ',
                ],
                'title' => 'Gestor de locación',
            ],
            'node_management' => [
                'allocations' => [
                    'description' => 'Permite ver todas las asignaciones de recursos en el panel para todos los nodos.',
                    'title' => 'Lista de asignaciones',
                ],
                'create' => [
                    'description' => 'Permite crear un nuevo nodo en el sistema.',
                    'title' => 'Crear Nodo',
                ],
                'delete' => [
                    'description' => 'Permite la eliminación de nodos',
                    'title' => 'Eliminar Nodos',
                ],
                'list' => [
                    'description' => 'Permite listado de todos los nodos existentes en el sistema.',
                    'title' => 'Enumerar nodos',
                ],
                'title' => 'Gestión de nodos',
                'view' => [
                    'description' => 'Permite ver detalles sobre un nodo en especifico incluyendo servicios activos.',
                    'title' => 'Lista de nodo único',
                ],
            ],
            'server_management' => [
                'build' => [
                    'description' => 'Permite la modificación los parámetros del servidor como memoria, CPU, y espacio del disco junto con una un asignado y IPs por defecto.',
                    'title' => 'Actualizar parámetros',
                ],
                'command' => [
                    'description' => 'Permite un usuario enviar comandos a un servidor en especifico.',
                    'title' => 'Enviar comando',
                ],
                'config' => [
                    'description' => 'Permite modificar configuraciones del servidor (nombre, dueño, y token de acceso).',
                    'title' => 'Actualizar configuraciones',
                ],
                'create' => [
                    'description' => 'Permite crear un nuevo servidor en el sistema.',
                    'title' => 'Crear servidor',
                ],
                'delete' => [
                    'description' => 'Permite eliminar un servidor del sistema.',
                    'title' => 'Eliminar servidor',
                ],
                'list' => [
                    'description' => 'Permite listar todos los servidores actualmente en el sistema.',
                    'title' => 'Listar servidores',
                ],
                'power' => [
                    'description' => 'Permite acceso al control del encendido y apagado del servidor.',
                    'title' => 'Estado de funcionamiento del servidor',
                ],
                'server' => [
                    'description' => 'Permite acceso a la visualización de la información para un solo servidor, incluyendo estadísticas y asignación de recursos.',
                    'title' => 'Información del servidor',
                ],
                'suspend' => [
                    'description' => 'Permite suspender una instancia del servidor.',
                    'title' => 'Suspender servidor',
                ],
                'title' => 'Administrar servidor',
                'unsuspend' => [
                    'description' => 'Permite eliminar la suspensión de una instancia del servidor.',
                    'title' => 'Reactivar servidor',
                ],
                'view' => [
                    'description' => 'Permite ver los detalles sobre un servidor específico incluyendo el daemon_token además de la información actual de procesos.',
                    'title' => 'Mostrar solo este servidor',
                ],
            ],
            'service_management' => [
                'list' => [
                    'description' => 'Permite listar todos los servicios configurados en el sistema.',
                    'title' => 'Enumerar servicios',
                ],
                'title' => 'Administración de servicios',
                'view' => [
                    'description' => 'Permite listar detalles sobre cada servicio en el sistema incluyendo opciones y variables.',
                    'title' => 'Listar solo este servicio',
                ],
            ],
            'user_management' => [
                'create' => [
                    'description' => 'Permite crear un nuevo usuario en el sistema.',
                    'title' => 'Crear usuario',
                ],
                'delete' => [
                    'description' => 'Permite borrar un usuario.',
                    'title' => 'Eliminar usuario',
                ],
                'list' => [
                    'description' => 'Permite listado de todos los usuarios existentes en el sistema.',
                    'title' => 'Lista de usuarios',
                ],
                'title' => 'Gestor de usuario',
                'update' => [
                    'description' => 'Permite la modificación de detalles de usuario (correo, contraseña, información TOTP).',
                    'title' => 'Actualizar usuario',
                ],
                'view' => [
                    'description' => 'Permite ver los detalles sobre un usuario especifico, incluyendo los servicios activos.',
                    'title' => 'Lista de usuario único',
                ],
            ],
        ],
        'permissions' => [
            'admin' => [
                'location' => [
                    'list' => [
                        'desc' => 'Permite el listado de locaciones y sus nodos asociados.',
                        'title' => 'Lista de locaciones',
                    ],
                ],
                'location_header' => 'Control de locación',
                'node' => [
                    'create' => [
                        'desc' => 'Permite la creación de un nuevo nodo en el sistema.',
                        'title' => 'Crear Nodo',
                    ],
                    'delete' => [
                        'desc' => 'Permite la eliminación de un nodo desde el sistema.',
                        'title' => 'Eliminar Nodo',
                    ],
                    'list' => [
                        'desc' => 'Permite el listado de todos los nodos existentes en el sistema.',
                        'title' => 'Lista de Nodos',
                    ],
                    'view-config' => [
                        'desc' => 'Peligro. Esto permite la vista del archivo de configuración que es usado por daemon, y expone los tokens secreto de daemon.',
                        'title' => 'Ver configuración de los nodos',
                    ],
                    'view' => [
                        'desc' => 'Permite ver detalles sobre un nodo especifico incluyendo servicios activos.',
                        'title' => 'Ver Nodo',
                    ],
                ],
                'node_header' => 'Control del Nodo',
                'option' => [
                    'list' => [
                        'title' => 'Lista de opciones',
                    ],
                    'view' => [
                        'title' => 'Ver opciones ',
                    ],
                ],
                'option_header' => 'Opciones de Control',
                'pack' => [
                    'list' => [
                        'title' => 'Lista de paquetes',
                    ],
                    'view' => [
                        'title' => 'Ver paquete',
                    ],
                ],
                'pack_header' => 'Control de paquete',
                'server' => [
                    'create' => [
                        'desc' => 'Permite la creación de un nuevo servidor en el sistema.',
                        'title' => 'Crear servidor',
                    ],
                    'delete' => [
                        'desc' => 'Permite la eliminación de un servidor desde el sistema.',
                        'title' => 'Eliminar Servidor',
                    ],
                    'edit-build' => [
                        'desc' => 'Permite la edición de los parámetros del servidor como la asignación de CPU y memoria.',
                        'title' => 'Editar parámetros del servidor',
                    ],
                    'edit-container' => [
                        'desc' => 'Permite la modificación del contenedor de docker el cual el servidor ejecutara.',
                        'title' => 'Editar contenedor del servidor',
                    ],
                    'edit-details' => [
                        'desc' => 'Permite la edición de detalles del servidor como nombre, dueño, descripción, Key secreta.',
                        'title' => 'Edita detalles del servidor',
                    ],
                    'edit-startup' => [
                        'desc' => 'Permite la modificación del comando de inicio y parámetros del servidor.',
                        'title' => 'Editar inicio del servidor',
                    ],
                    'install' => [
                        'title' => 'Cambiar estado de instalación',
                    ],
                    'list' => [
                        'desc' => 'Permite listado de todos los servidores existentes en el sistema.',
                        'title' => 'Lista de servidores',
                    ],
                    'rebuild' => [
                        'title' => 'Re instalar servidor',
                    ],
                    'suspend' => [
                        'desc' => 'Permite la suspensión y quitar suspensiones a un servidor.',
                        'title' => 'Suspender servidor',
                    ],
                    'view' => [
                        'desc' => 'Permite ver un solo servidor incluyendo servicios y detalles.',
                        'title' => 'Ver servidor',
                    ],
                ],
                'server_header' => 'Control del servidor',
                'service' => [
                    'list' => [
                        'desc' => 'Permite listado de todos los servicios configurado en el sistema.',
                        'title' => 'Lista de servicios',
                    ],
                    'view' => [
                        'desc' => 'Permite listado de detalles sobre el servicio en el sistema incluyendo opciones del servicio y variables.',
                        'title' => 'Ver servicio',
                    ],
                ],
                'service_header' => 'Control de servicio',
                'user' => [
                    'create' => [
                        'desc' => 'Permite la creación de un nuevo usuario en el sistema.',
                        'title' => 'Crear usuario',
                    ],
                    'delete' => [
                        'desc' => 'Permite la eliminación de un usuario.',
                        'title' => 'Eliminar usuario',
                    ],
                    'edit' => [
                        'desc' => 'Permite modificaciones de detalles del usuario.',
                        'title' => 'Actualizar usuario',
                    ],
                    'list' => [
                        'desc' => 'Permite listado de todos los usuarios existente en el sistema.',
                        'title' => 'Lista de usuarios',
                    ],
                    'view' => [
                        'desc' => 'Permite ver detalles sobre un usuario en especifico incluyendo servicios activos.',
                        'title' => 'Ver usuario',
                    ],
                ],
                'user_header' => 'Control de usuario',
            ],
            'user' => [
                'server' => [
                    'command' => [
                        'desc' => 'Permite envió de un comando a un servidor activo.',
                        'title' => 'Enviar comando.',
                    ],
                    'list' => [
                        'desc' => 'Permite listado de todos los servidores a un usuario que sea propietario o tenga acceso a los subusuarios.',
                        'title' => 'Lista de servidores',
                    ],
                    'power' => [
                        'desc' => 'Permite cambiar el estatus de poder para un servidor.',
                        'title' => 'Alternar poder',
                    ],
                    'view' => [
                        'desc' => 'Permite ver un servidor en especifico que un usuario pueda acceder.',
                        'title' => 'Ver servidor',
                    ],
                ],
                'server_header' => 'Permisos de usuario en el servidor',
            ],
        ],
    ],
    'confirm' => 'Esta seguro?',
    'errors' => [
        '403' => [
            'desc' => 'No tienes permiso para acceder a este recurso en este servidor.',
            'header' => 'Prohibido',
        ],
        '404' => [
            'desc' => 'No hemos podido localizar el recurso solicitado en este servidor.',
            'header' => '404 archivo no encontrado',
        ],
        'home' => 'Ir a Home',
        'installing' => [
            'desc' => 'El servidor solicitante sigue completando el proceso de instalación. Por favor hecha un vistazo en unos minutos, recibirá un correo tan pronto el proceso se haya completado.',
            'header' => 'Instalando servidor',
        ],
        'return' => 'Volver a la página anterior',
        'suspended' => [
            'desc' => 'El servidor a sido suspendido y no podrá ser accedido.',
            'header' => 'Servidor suspendido',
        ],
    ],
    'form_error' => 'El siguiente error fuer encontrado mientras se estaba procesando el pedido.',
    'index' => [
        'header' => 'Consola del servidor',
        'header_sub' => 'Controla tu servidor en tiempo real.',
        'list' => 'Lista de servidores',
    ],
    'no_servers' => 'Actualmente no tienes ningún servidor asignado en tu cuenta.',
    'password_req' => 'La contraseña de tener los siguiente requerimientos: por lo menos una letra en mayúscula, una letra en minúscula, un dígito y, debe ser como mínimo 8 caracteres.',
    'security' => [
        '2fa_checkpoint_help' => 'Utilice el 2FA aplicación en su teléfono para tomar una foto del código QR de la izquierda, o introducir manualmente el código debajo de ella. Una vez hecho esto, generar un token y entrar en él a continuación.',
        '2fa_disabled' => '2-Factor de Autenticación está deshabilitado en tu cuenta! Usted debe habilitar 2FA con el fin de añadir un nivel extra de protección en su cuenta.',
        '2fa_disable_error' => 'El 2FA token proporcionado no es válido. La protección no ha sido deshabilitado para esta cuenta.',
        '2fa_enabled' => '2-Factor de Autenticación está habilitada en esta cuenta y será necesario iniciar la sesión en el panel de. Si usted desea deshabilitar el 2FA, simplemente ingrese un token válido a continuación y envíe el formulario.',
        '2fa_header' => '2-Factor De Autenticación',
        '2fa_qr' => 'Confgure 2FA en Su Dispositivo',
        '2fa_token_help' => 'Introduzca el 2FA Token generado por la aplicación (Google Authenticator, Authy, etc.).',
        'disable_2fa' => 'Deshabilitar 2-Factor De Autenticación',
        'enable_2fa' => 'Habilitar 2-Factor De Autenticación',
        'header' => 'Seguridad De La Cuenta',
        'header_sub' => 'Control de sesiones activas y 2-Factor de Autenticación.',
        'sessions' => 'Sesiones Activas',
        'session_mgmt_disabled' => 'Su anfitrión no ha habilitado la capacidad de gestionar la cuenta de las sesiones a través de esta interfaz.',
    ],
    'server_name' => 'Nombre del servidor',
    'validation_error' => 'Hubo un error con uno o más campos en la solicitud.',
];
