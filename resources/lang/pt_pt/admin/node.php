<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'validation' => [
        'fqdn_not_resolvable' => 'O FQDN ou endereço IP fornecido não resolve para um endereço IP válido.',
        'fqdn_required_for_ssl' => 'Um nome de domínio totalmente qualificado que resolva para um endereço IP público é necessário para usar SSL para este nó.',
    ],
    'notices' => [
        'allocations_added' => 'As alocações foram adicionadas com sucesso a este nó.',
        'node_deleted' => 'O nó foi removido com sucesso do painel.',
        'location_required' => 'Você deve ter pelo menos um local configurado antes de adicionar um nó a este painel.',
        'node_created' => 'Novo nó criado com sucesso. Você pode configurar automaticamente o daemon nesta máquina visitando a aba \'Configuration\' tab. <strong>Antes de adicionar qualquer servidor, você deve primeiro alocar pelo menos um endereço IP e porta.</strong>',
        'node_updated' => 'As informações do nó foram atualizadas. Se alguma configuração do daemon for alterada, você precisará reiniciá-lo para que as alterações tenham efeito.',
        'unallocated_deleted' => 'Excluiu todas as portas não alocadas para <code>:ip</code>.',
    ],
];
