<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>
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
