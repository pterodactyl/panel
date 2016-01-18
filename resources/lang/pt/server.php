<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Pterodactyl Language Strings for /server/{server} Routes
    |--------------------------------------------------------------------------
    */
    'ajax' => [
        'socket_error' => 'Nós não conseguimos se conectar ao servidor principal do Socket.IO, talvez tenha problemas de conexão acontecendo. O painel pode não funcionar como esperado.',
        'socket_status' => 'O estado desse servidor foi alterado para',
        'socket_status_crashed' => 'Esse server foi detectado como CRASHED.',
    ],
    'index' => [
        'add_new' => 'Adicionar novo servidor',
        'memory_use' => 'Uso de Memória',
        'cpu_use' => 'Uso de CPU',
        'xaxis' => 'Tempo (Incremento de 2s)',
        'server_info' => 'Informações do Servidor',
        'connection' => 'Conexão Padrão',
        'mem_limit' => 'Limite de Memória',
        'disk_space' => 'Espaço em Disco',
        'control' => 'Controlar Servidor',
        'usage' => 'Uso',
        'allocation' => 'Alocação',
        'command' => 'Enviar Comando de Console',
    ],
    'files' => [
            'loading' => 'Carregando lista de arquivos, isso pode levar alguns segundos...',
            'yaml_notice' => 'Você está atualmente editando um arquivo YAML. Esses arquivos não aceitam tabs, eles precisam usar espaços. Nós fomos além disso e quando você aprtar tab :dropdown espaços serão colocados.',
            'back' => 'Voltar ao Gerenciador de Arquivos',
            'saved' => 'Arquivo salvo com sucesso.',
    ],
];
