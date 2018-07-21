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
    'notices' => [
        'created' => 'Um novo ninho, :name, foi criado com sucesso.',
        'deleted' => 'Apaguei com sucesso o ninho solicitado do Painel.',
        'updated' => 'Atualizou com sucesso as opções de configuração do aninhamento.',
    ],
    'eggs' => [
        'notices' => [
            'imported' => 'Importado com sucesso este Egg e suas variáveis associadas.',
            'updated_via_import' => 'Este ovo foi atualizado usando o arquivo fornecido.',
            'deleted' => 'A configuração do ovo foi atualizada com sucesso.',
            'updated' => 'A configuração do ovo foi atualizada com sucesso.',
            'script_updated' => 'O script de instalação do ovo foi atualizado e será executado sempre que os servidores estiverem instalados.',
            'egg_created' => 'Um novo ovo foi lançado com sucesso. Você precisará reiniciar quaisquer daemons em execução para aplicar esse novo ovo.',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => 'A variável ":variable" foi excluída e não estará mais disponível para os servidores depois de recriada.',
            'variable_updated' => 'A variável ":variable" foi atualizada. Você precisará reconstruir qualquer servidor usando essa variável para aplicar as alterações.',
            'variable_created' => 'Nova variável foi criada com sucesso e atribuída a este ovo.',
        ],
    ],
];
