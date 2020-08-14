<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

return [
    'notices' => [
        'created' => 'Um novo ninho, :name, foi criado com sucesso.',
        'deleted' => 'Successfully deleted the requested nest from the Panel.',
        'updated' => 'Successfully updated the nest configuration options.',
    ],
    'eggs' => [
        'notices' => [
            'imported' => 'Importou com sucesso este Ovo e suas variáveis associadas.',
            'updated_via_import' => 'Este Egg foi atualizado usando o arquivo fornecido.',
            'deleted' => 'Excluído com sucesso o ovo solicitado do painel.',
            'updated' => 'A configuração do ovo foi atualizada com sucesso.',
            'script_updated' => 'O script de instalação do Egg foi atualizado e será executado sempre que os servidores forem instalados.',
            'egg_created' => 'Um novo ovo foi posto com sucesso. Você precisará reiniciar todos os daemons em execução para aplicar este novo ovo.',
        ],
    ],
    'variables' => [
        'notices' => [
            'variable_deleted' => 'A variável ":variable" foi excluído e não estará mais disponível para os servidores depois de reconstruído.',
            'variable_updated' => 'A variável ":variable" Tem sido atualizado. Você precisará reconstruir todos os servidores usando esta variável para aplicar as mudanças.',
            'variable_created' => 'Nova variável foi criada com sucesso e atribuída a este ovo.',
        ],
    ],
];
