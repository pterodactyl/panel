<?php

return [
    'sign_in' => 'Sign In',
    'go_to_login' => 'Ir para o Login',
    'failed' => 'Nenhuma conta que corresponda a essas credenciais foi encontrada.',

    'forgot_password' => [
        'label' => 'Esqueceu-se da senha?',
        'label_help' => 'Digite o endereço de e-mail da sua conta para receber instruções sobre como redefinir sua senha.',
        'button' => 'Recuperar a conta',
    ],

    'reset_password' => [
        'button' => 'Redefinir senha e fazer login',
    ],

    'two_factor' => [
        'label' => '2-Factor Token',
        'label_help' => 'Esta conta requer uma segunda camada de autenticação para continuar. Por favor, insira o código gerado pelo seu dispositivo para completar este login.',
        'checkpoint_failed' => 'O token de autenticação de dois fatores é inválido.',
    ],

    'throttle' => 'Muitas tentativas de login. Por favor tente novamente em :seconds segundos.',
    'password_requirements' => 'A senha deve ter pelo menos 8 caracteres e deve ser nova/exclusiva para este site.',
    '2fa_must_be_enabled' => 'O administrador exigiu que a autenticação de 2 fatores seja ativada para sua conta para usar o painel.',
];
