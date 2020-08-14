<?php

return [
    'daemon_connection_failed' => 'Houve uma exceção ao tentar se comunicar com o daemon, resultando em um HTTP/:code código de resposta. Esta exceção foi registrada.',
    'node' => [
        'servers_attached' => 'Um nó não deve ter servidores vinculados a ele para ser excluído.',
        'daemon_off_config_updated' => 'A configuração do daemon <strong>foi atualizada</strong>, no entanto, foi encontrado um erro ao tentar atualizar automaticamente o arquivo de configuração no Daemon. Você precisará atualizar manualmente o arquivo de configuração (core.json) para o daemon para aplicar essas mudanças.',
    ],
    'allocations' => [
        'server_using' => 'Um servidor está atualmente atribuído a esta alocação. Uma alocação só pode ser excluída se nenhum servidor estiver atualmente atribuído.',
        'too_many_ports' => 'Adicionar mais de 1000 portas em um único intervalo de uma vez não é compatível.',
        'invalid_mapping' => 'O mapeamento fornecido para :port era inválido e não pôde ser processado.',
        'cidr_out_of_range' => 'CIDR notação só permite máscaras entre /25 e /32.',
        'port_out_of_range' => 'As portas em uma alocação devem ser maiores que 1024 e menores ou iguais a 65535.',
    ],
    'nest' => [
        'delete_has_servers' => 'Um Nest com servidores ativos anexados a ele não pode ser excluído do Painel.',
        'egg' => [
            'delete_has_servers' => 'Um ovo com servidores ativos anexados a ele não pode ser excluído do painel.',
            'invalid_copy_id' => 'O Egg selecionado para copiar um script não existe ou está copiando um script próprio.',
            'must_be_child' => 'A diretiva "Copiar configurações de" para este Egg deve ser uma opção secundária para o Nest selecionado.',
            'has_children' => 'Este ovo é o pai de um ou mais ovos. Exclua esses ovos antes de excluir este ovo.',
        ],
        'variables' => [
            'env_not_unique' => 'A variável de ambiente: nome deve ser único para este Ovo.',
            'reserved_name' => 'A variável de ambiente: o nome é protegido e não pode ser atribuído a uma variável.',
            'bad_validation_rule' => 'A regra de validação ":rule" não é uma regra válida para este aplicativo.',
        ],
        'importer' => [
            'json_error' => 'Ocorreu um erro ao tentar analisar o arquivo JSON: :error.',
            'file_error' => 'O arquivo JSON fornecido não era válido.',
            'invalid_json_provided' => 'O arquivo JSON fornecido não está em um formato que possa ser reconhecido.',
        ],
    ],
    'packs' => [
        'delete_has_servers' => 'Não é possível excluir um pacote que está anexado a servidores ativos.',
        'update_has_servers' => 'Não é possível modificar o ID da opção associada quando os servidores estão atualmente anexados a um pacote.',
        'invalid_upload' => 'O arquivo fornecido não parece ser válido.',
        'invalid_mime' => 'O arquivo fornecido não atende ao tipo exigido :type',
        'unreadable' => 'O arquivo fornecido não pôde ser aberto pelo servidor.',
        'zip_extraction' => 'Foi encontrada uma exceção ao tentar extrair o arquivo fornecido para o servidor.',
        'invalid_archive_exception' => 'O arquivo do pacote fornecido parece estar faltando um arquivo necessário archive.tar.gz ou import.json no diretório raiz.',
    ],
    'subusers' => [
        'editing_self' => 'Editar sua própria conta de subusuário não é permitido.',
        'user_is_owner' => 'Você não pode adicionar o proprietário do servidor como um subusuário para este servidor.',
        'subuser_exists' => 'Um usuário com esse endereço de e-mail já foi atribuído como subusuário para este servidor.',
    ],
    'databases' => [
        'delete_has_databases' => 'Não é possível excluir um servidor host de banco de dados que possui bancos de dados ativos vinculados a ele.',
    ],
    'tasks' => [
        'chain_interval_too_long' => 'O intervalo máximo de tempo para uma tarefa em cadeia é de 15 minutos.',
    ],
    'locations' => [
        'has_nodes' => 'Não é possível excluir um local com nós ativos anexados a ele.',
    ],
    'users' => [
        'node_revocation_failed' => 'Falha ao revogar chaves em <a href=":link">Node #:node</a>. :error',
    ],
    'deployment' => [
        'no_viable_nodes' => 'Nenhum nó que satisfaça os requisitos especificados para implantação automática foi encontrado.',
        'no_viable_allocations' => 'Nenhuma alocação que satisfaça os requisitos para implantação automática foi encontrada.',
    ],
    'api' => [
        'resource_not_found' => 'O recurso solicitado não existe neste servidor.',
    ],
];
