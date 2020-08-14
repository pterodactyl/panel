<?php

return [
    'email' => [
        'title' => 'Atualizar o seu email',
        'updated' => 'Seu endereço de e-mail foi atualizado.',
    ],
    'password' => [
        'title' => 'Mude a sua senha',
        'requirements' => 'A sua nova senha deve ter pelo menos 8 caracteres.',
        'updated' => 'A sua senha foi atualizada com sucesso.',
    ],
    'two_factor' => [
        'button' => 'Configure a sua autenticação de 2 fatores',
        'disabled' => 'A autenticação de dois fatores foi desativada na sua conta. Você não será mais solicitado a fornecer um token ao fazer login.',
        'enabled' => 'A autenticação de dois fatores foi ativada na sua conta! A partir de agora, ao efetuar login, você será solicitado a fornecer o código gerado pelo seu dispositivo.',
        'invalid' => 'O token fornecido é inválido.',
        'setup' => [
            'title' => 'Configurar autenticação de dois fatores',
            'help' => 'Não consegue ler o código? Insira o código abaixo em seu aplicativo:',
            'field' => 'Digite o token',
        ],
        'disable' => [
            'title' => 'Desative a autenticação de dois fatores',
            'field' => 'Digite o token',
        ],
    ],
];
