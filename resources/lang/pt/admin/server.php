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
    'exceptions' => [
        'no_new_default_allocation' => 'Você está tentando excluir a alocação padrão para este servidor, mas não há alocação de fallback para usar.',
        'marked_as_failed' => 'Este servidor foi marcado como tendo falhado em uma instalação anterior. O status atual não pode ser alternado nesse estado.',
        'bad_variable' => 'Houve um erro de validação com o :name variable.',
        'daemon_exception' => 'Houve uma exceção ao tentar se comunicar com o daemon, resultando em um HTTP/:code Código de resposta. Esta exceção foi registrada.',
        'default_allocation_not_found' => 'A alocação padrão solicitada não foi encontrada neste server\'s alocações.',
    ],
    'alerts' => [
        'startup_changed' => 'A configuração de inicialização deste servidor foi atualizada. Se este server\'s ninho ou ovo foi alterado uma reinstalação estará ocorrendo agora.',
        'server_deleted' => 'O servidor foi excluído com sucesso do sistema.',
        'server_created' => 'Servidor foi criado com sucesso no painel. Por favor, aguarde o daemon alguns minutos para instalar completamente este servidor.',
        'build_updated' => 'Os detalhes da compilação deste servidor foram atualizados. Algumas alterações podem exigir que uma reinicialização entre em vigor.',
        'suspension_toggled' => 'O status de suspensão do servidor foi alterado para :status.',
        'rebuild_on_boot' => 'Este servidor foi marcado como requerendo uma reconstrução do Contêiner do Docker. Isso acontecerá na próxima vez em que o servidor for iniciado.',
        'install_toggled' => 'O status da instalação deste servidor foi alternado.',
        'server_reinstalled' => 'Este servidor foi enfileirado para uma reinstalação a partir de agora.',
        'details_updated' => 'Detalhes do servidor foram atualizados com sucesso.',
        'docker_image_updated' => 'Alterou com sucesso a imagem do Docker padrão para usar neste servidor. Uma reinicialização é necessária para aplicar essa alteração.',
        'node_required' => 'Você deve ter pelo menos um nó configurado antes de poder adicionar um servidor a este painel.',
    ],
];
