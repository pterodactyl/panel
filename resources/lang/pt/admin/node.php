<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
  * Tradução PT (c) 2018 CenatHostBR <contato@cenathostbr.com>.
  *  Traduzido por: Patrick Oliveira <patricknasci@hotmail.com>.
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'validation' => [
        'fqdn_not_resolvable' => 'O FQDN ou endereço IP fornecido não resolve um endereço IP válido.',
        'fqdn_required_for_ssl' => 'Um nome de domínio totalmente qualificado que resolve um endereço IP público é necessário para usar o SSL nesse nó.',
    ],
    'notices' => [
        'allocations_added' => 'As alocações foram adicionadas com sucesso a este nó.',
        'node_deleted' => 'O nó foi removido com sucesso do painel',
        'location_required' => 'Você deve ter pelo menos um local configurado antes de poder adicionar um nó a este painel.',
        'node_created' => 'Nó criado com sucesso. Você pode configurar automaticamente o daemon nesta máquina, visitando o \'Configuration\' Aba. <strong>Antes de adicionar qualquer servidor, você deve primeiro alocar pelo menos um endereço IP e uma porta.</strong>',
        'node_updated' => 'As informações do nó foram atualizadas. Se alguma configuração do daemon tiver sido alterada, será necessário reinicializá-lo para que essas alterações entrem em vigor.',
        'unallocated_deleted' => 'Excluiu todas as portas não alocadas para <code>:ip</code>.',
    ],
];
