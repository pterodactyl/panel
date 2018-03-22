<?php

return [
    'validation_error' => 'Houve um erro com um ou mais campos nesta requisição.',
    'errors' => [
        'return' => 'Retornar à página anterior',
        'home' => 'Voltar para a Home',
        '403' => [
            'header' => 'Proibido',
            'desc' => 'Você não tem permissão para acessar este recurso no servidor.',
        ],
        '404' => [
            'header' => 'Arquivo não encontrado',
            'desc' => 'Não foi possível localizar o recurso solicitado no servidor.',
        ],
        'installing' => [
            'header' => 'Instalando servidor',
            'desc' => 'O servidor solicitado ainda está completando o processo de instalação. Por favor volte em alguns minutos, você deverá receber um e-mail assim que concluído este processo.',
        ],
        'suspended' => [
            'header' => 'Servidor suspenso',
            'desc' => 'Este servidor foi suspenso e não pode ser acessado.',
        ],
    ],
    'index' => [
        'header' => 'Seus servidores',
        'header_sub' => 'Servidores que você tem acesso.',
        'list' => 'Lista de servidores',
    ],
    'api' => [
        'index' => [
            'list' => 'Suas chaves',
            'header' => 'API de conta',
            'header_sub' => 'Gerenciar chaves de acesso que permitem que você execute ações neste painel.',
            'create_new' => 'Criar nova chave de API',
            'keypair_created' => 'Uma chave de API foi gerada com sucesso e está listada abaixo.',
        ],
        'new' => [
            'header' => 'Nova chave de API',
            'header_sub' => 'Criar uma nova chave de acesso da conta.',
            'form_title' => 'Detalhes',
            'descriptive_memo' => [
                'title' => 'Descrição',
                'description' => 'Inserir uma breve descrição desta chave que serão úteis para referência.',
            ],
            'allowed_ips' => [
                'title' => 'IPs permitidos',
                'description' => 'Insira uma lista delimitada por linha de IPs com permissão para acessar a API usando essa chave. A notação CIDR é permitida. Deixe em branco para permitir qualquer IP.',
            ],
        ],
    ],
    'account' => [
        'details_updated' => 'Os detalhes de sua conta foram atualizados com sucesso.',
        'invalid_password' => 'A senha fornecida para sua conta não é válida.',
        'header' => 'Sua conta',
        'header_sub' => 'Gerenciar detalhes da sua conta.',
        'update_pass' => 'Alterar Senha',
        'update_email' => 'Alterar E-mail',
        'current_password' => 'Senha Atual',
        'new_password' => 'Nova Senha',
        'new_password_again' => 'Repita a Nova Senha',
        'new_email' => 'Novo Endereço de Email',
        'first_name' => 'Primeiro Nome',
        'last_name' => 'Ultimo Nome',
        'update_identitity' => 'Alterar Identidade',
        'username_help' => 'Seu nome de usuário precisa ser único, e somente conter os caracteres: :requirements.',
    ],
    'security' => [
        'session_mgmt_disabled' => 'Seu host não habilitou a capacidade de gerenciar sessões de conta por meio dessa interface.',
        'header' => 'Segurança da Conta',
        'header_sub' => 'Controle as sessões ativas e a autenticação de 2 fatores.',
        'sessions' => 'Sessões Ativas',
        '2fa_header' => 'Autenticação em 2 Fatores',
        '2fa_token_help' => 'Digite o token 2FA gerado pelo seu aplicativo (Google Authenticator, Authy, etc.).',
        'disable_2fa' => 'Desabilitar a Autenticação em 2 Fatores',
        '2fa_enabled' => 'A autenticação de 2 fatores está ativada nesta conta e será necessária para fazer login no painel. Se você gostaria de desativar 2FA, basta digitar um token válido abaixo e enviar o formulário.',
        '2fa_disabled' => 'Autenticação de 2 fatores está desativada na sua conta! Você deve ativar o 2FA para adicionar um nível extra de proteção à sua conta.',
        'enable_2fa' => 'Habilitar a Autenticação em 2 Fatores',
        '2fa_qr' => 'Configurar 2FA no seu dispositivo',
        '2fa_checkpoint_help' => 'Use o aplicativo 2FA em seu telefone para tirar uma foto do código QR à esquerda ou insira manualmente o código abaixo dele. Depois de ter feito isso, gere um token e insira-o abaixo.',
        '2fa_disable_error' => 'O token 2FA fornecido não é válido. A proteção não foi desativada para esta conta.',
    ],
];
