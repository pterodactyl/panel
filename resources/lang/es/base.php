<?php

return [
    'validation_error' => 'Hubo un error con uno o más campos en la solicitud.',
    'errors' => [
        'return' => 'Regresar a la Página Anterior',
        'home' => 'Ir A Casa',
        '403' => [
            'header' => 'Prohibido',
            'desc' => 'Usted no tiene permiso para acceder a este recurso en este servidor.',
        ],
        '404' => [
            'header' => 'No Se Encuentra El Archivo',
            'desc' => 'No hemos podido localizar el recurso solicitado en el servidor.',
        ],
        'installing' => [
            'header' => 'El Servidor De Instalación',
            'desc' => 'El servidor solicitado aún no ha finalizado el proceso de instalación. Por favor, vuelva en unos pocos minutos, usted debe recibir un correo electrónico tan pronto como este proceso se haya completado.',
        ],
        'suspended' => [
            'header' => 'Servidor Suspendido',
            'desc' => 'Este servidor ha sido suspendido y no se puede acceder.',
        ],
    ],
    'index' => [
        'header' => 'Sus Servidores',
        'header_sub' => 'Los servidores que tienen acceso a.',
        'list' => 'Lista De Servidor',
    ],
    'api' => [
        'index' => [
            'header' => 'El Acceso a la API',
            'header_sub' => 'Gestionar su acceso a la API de teclas.',
            'list' => 'Claves de API',
            'create_new' => 'Crear Nueva clave de API',
            'keypair_created' => 'Una API Key-Pair se ha generado. Su API token secreto es <code>:token</code>. Por favor, tome nota de esta clave como no se mostrará de nuevo.',
        ],
        'new' => [
            'header' => 'Nueva Clave de API',
            'header_sub' => 'Crear una API nueva clave de acceso',
            'form_title' => 'Detalles',
            'descriptive_memo' => [
                'title' => 'Descriptivo Memo',
                'description' => 'Escriba una breve descripción de lo que esta clave de API se utiliza para.',
            ],
            'allowed_ips' => [
                'title' => 'IPs Permitidas',
                'description' => 'Escriba una línea acotada lista de IPs que tienen permitido el acceso a la API usando esta clave. La notación CIDR es permitido. Dejar en blanco para permitir que cualquier IP.',
            ],
        ],
        'permissions' => [
            'user' => [
                'server_header' => 'Usuario Permisos De Servidor',
                'server' => [
                    'list' => [
                        'title' => 'Lista De Los Servidores',
                        'desc' => 'Permite listado de todos los servidores de un usuario posee o tiene acceso a un subuser.',
                    ],
                    'view' => [
                        'title' => 'Vista Del Servidor',
                        'desc' => 'Permite la visualización de servidor específico de usuario puede tener acceso a.',
                    ],
                    'power' => [
                        'title' => 'Alternar El Poder',
                        'desc' => 'Permitir la activación o desactivación de estado de energía para un servidor.',
                    ],
                    'command' => [
                        'title' => 'Enviar Comando',
                        'desc' => 'Permitir el envío de un comando a un servidor en ejecución.',
                    ],
                ],
            ],
            'admin' => [
                'server_header' => 'Control De Servidor',
                'server' => [
                    'list' => [
                        'title' => 'Lista De Los Servidores',
                        'desc' => 'Permite listado de todos los servidores en la actualidad en el sistema de.',
                    ],
                    'view' => [
                        'title' => 'Vista Del Servidor',
                        'desc' => 'Permite ver de un solo servidor, incluyendo los de servicio y los detalles.',
                    ],
                    'delete' => [
                        'title' => 'Eliminar Servidor',
                        'desc' => 'Permite la eliminación de un servidor del sistema.',
                    ],
                    'create' => [
                        'title' => 'Crear Servidor',
                        'desc' => 'Permite la creación de un nuevo servidor en el sistema.',
                    ],
                    'edit-details' => [
                        'title' => 'Editar Los Detalles Del Servidor De',
                        'desc' => 'Permite la edición de los datos del servidor, tales como nombre, propietario, descripción y clave secreta.',
                    ],
                    'edit-container' => [
                        'title' => 'Editar Servidor De Contenedor',
                        'desc' => 'Permite la modificación de la ventana acoplable contenedor el servidor se ejecuta en.',
                    ],
                    'suspend' => [
                        'title' => 'Suspender Servidor',
                        'desc' => 'Permite la suspensión y unsuspension de un determinado servidor.',
                    ],
                    'install' => [
                        'title' => 'Alternar El Estado De Instalación',
                        'desc' => '',
                    ],
                    'rebuild' => [
                        'title' => 'Reconstruir Servidor',
                        'desc' => '',
                    ],
                    'edit-build' => [
                        'title' => 'Edición De Compilación Del Servidor',
                        'desc' => 'Permite la edición de compilación del servidor de configuración de la CPU y de la memoria de las asignaciones.',
                    ],
                    'edit-startup' => [
                        'title' => 'Editar El Inicio Del Servidor',
                        'desc' => 'Permite la modificación de servidor de comandos de inicio y los parámetros de.',
                    ],
                ],
                'location_header' => 'Control De Ubicación De',
                'location' => [
                    'list' => [
                        'title' => 'Lista De Ubicaciones',
                        'desc' => 'Permite listado de todos los lugares y sus nodos asociados.',
                    ],
                ],
                'node_header' => 'Nodo De Control',
                'node' => [
                    'list' => [
                        'title' => 'Lista De Nodos',
                        'desc' => 'Permite listado de todos los nodos en la actualidad en el sistema de.',
                    ],
                    'view' => [
                        'title' => 'Nodo Vista',
                        'desc' => 'Permite ver los detalles acerca de un determinado nodo, incluyendo los servicios activos.',
                    ],
                    'view-config' => [
                        'title' => 'Vista De Configuración De Nodo',
                        'desc' => 'Peligro. Esto permite la visualización de la configuración del nodo de archivo utilizado por el demonio, y expone secreto demonio tokens.',
                    ],
                    'create' => [
                        'title' => 'Crear Nodo',
                        'desc' => 'Permite la creación de un nuevo nodo en el sistema.',
                    ],
                    'delete' => [
                        'title' => 'Eliminar El Nodo',
                        'desc' => 'Permite la eliminación de un nodo del sistema.',
                    ],
                ],
                'user_header' => 'Control De Usuario',
                'user' => [
                    'list' => [
                        'title' => 'Los Usuarios De La Lista',
                        'desc' => 'Permite listado de todos los usuarios de la actualidad en el sistema de.',
                    ],
                    'view' => [
                        'title' => 'Vista De Usuario',
                        'desc' => 'Permite ver los detalles acerca de un usuario específico, incluyendo los servicios activos.',
                    ],
                    'create' => [
                        'title' => 'Crear Usuario',
                        'desc' => 'Permite crear un nuevo usuario en el sistema.',
                    ],
                    'edit' => [
                        'title' => 'Actualización De Usuario',
                        'desc' => 'Permite la modificación de datos del usuario.',
                    ],
                    'delete' => [
                        'title' => 'Eliminar Usuario',
                        'desc' => 'Permite la eliminación de un usuario.',
                    ],
                ],
                'service_header' => 'Servicio De Control De',
                'service' => [
                    'list' => [
                        'title' => 'Servicio De Lista De',
                        'desc' => 'Permite listado de todos los servicios configurados en el sistema.',
                    ],
                    'view' => [
                        'title' => 'Ver Servicio',
                        'desc' => 'Permite el listado de más detalles acerca de cada servicio en el sistema, incluyendo las opciones de servicio y variables.',
                    ],
                ],
                'option_header' => 'Opción De Control',
                'option' => [
                    'list' => [
                        'title' => 'Opciones De La Lista De',
                        'desc' => '',
                    ],
                    'view' => [
                        'title' => 'La Opción De Vista',
                        'desc' => '',
                    ],
                ],
                'pack_header' => 'Pack De Control De',
                'pack' => [
                    'list' => [
                        'title' => 'Lista De Paquetes De',
                        'desc' => '',
                    ],
                    'view' => [
                        'title' => 'Vista Pack',
                        'desc' => '',
                    ],
                ],
            ],
        ],
    ],
    'account' => [
        'details_updated' => 'Los detalles de su cuenta se han actualizado correctamente.',
        'invalid_password' => 'La contraseña proporcionada por su cuenta no era válido.',
        'header' => 'Su Cuenta',
        'header_sub' => 'Gestionar los detalles de su cuenta.',
        'update_pass' => 'Actualización De Contraseña',
        'update_email' => 'Actualización De La Dirección De Correo Electrónico',
        'current_password' => 'Contraseña Actual',
        'new_password' => 'Nueva Contraseña',
        'new_password_again' => 'Repetir Contraseña Nueva',
        'new_email' => 'Nueva Dirección De Correo Electrónico',
        'first_name' => 'Primer Nombre',
        'last_name' => 'Apellido',
        'update_identitity' => 'Actualización De La Identidad',
        'username_help' => 'Su nombre de usuario debe ser único a su cuenta, y sólo pueden contener los siguientes caracteres: :requirements.',
    ],
    'security' => [
        'session_mgmt_disabled' => 'Su anfitrión no ha habilitado la capacidad de gestionar la cuenta de las sesiones a través de esta interfaz.',
        'header' => 'Seguridad De La Cuenta',
        'header_sub' => 'Control de sesiones activas y 2-Factor de Autenticación.',
        'sessions' => 'Sesiones Activas',
        '2fa_header' => '2-Factor De Autenticación',
        '2fa_token_help' => 'Introduzca el 2FA Token generado por la aplicación (Google Authenticatior, Authy, etc.).',
        'disable_2fa' => 'Deshabilitar 2-Factor De Autenticación',
        '2fa_enabled' => '2-Factor de Autenticación está habilitada en esta cuenta y será necesario iniciar la sesión en el panel de. Si usted desea deshabilitar el 2FA, simplemente ingrese un token válido a continuación y envíe el formulario.',
        '2fa_disabled' => '2-Factor de Autenticación está deshabilitado en tu cuenta! Usted debe habilitar 2FA con el fin de añadir un nivel extra de protección en su cuenta.',
        'enable_2fa' => 'Habilitar 2-Factor De Autenticación',
        '2fa_qr' => 'Confgure 2FA en Su Dispositivo',
        '2fa_checkpoint_help' => 'Utilice el 2FA aplicación en su teléfono para tomar una foto del código QR de la izquierda, o introducir manualmente el código debajo de ella. Una vez hecho esto, generar un token y entrar en él a continuación.',
        '2fa_disable_error' => 'El 2FA token proporcionado no es válido. La protección no ha sido deshabilitado para esta cuenta.',
    ],
];
