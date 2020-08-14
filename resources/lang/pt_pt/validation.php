<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'O :attribute deve ser aceite.',
    'active_url' => 'O :attribute não é um URL válido.',
    'after' => 'O :attribute deve ser uma data após :date.',
    'after_or_equal' => 'O :attribute deve ser uma data inferior ou igual a :date.',
    'alpha' => 'O :attribute apenas pode conter letras.',
    'alpha_dash' => 'O :attribute só pode conter letras(a-Z), números(0-9) e travessões(-).',
    'alpha_num' => 'O :attribute apenas deve conter letras(a-Z) e numeros(0-9).',
    'array' => 'O :attribute deve ser um array.',
    'before' => 'O :attribute deve ser uma data antes de :date.',
    'before_or_equal' => 'O :attribute deve ser uma data anterior ou igual a :date.',
    'between' => [
        'numeric' => 'O :attribute deve estar entre :min e :max.',
        'file' => 'O :attribute deve estar entre :min e :max kilobytes.',
        'string' => 'O :attribute mdeve estar entre :min e :max caracteres.',
        'array' => 'O :attribute deve estar entre :min e :max itens.',
    ],
    'boolean' => 'O :attribute field deve ser verdadeiro ou falso.',
    'confirmed' => 'O :attribute confirmação não corresponde.',
    'date' => 'A :attribute não é uma data válida.',
    'date_format' => 'A :attribute não corresponde a um formato correto :format.',
    'different' => 'O :attribute e :other devem ser diferentes.',
    'digits' => 'O :attribute apenas deve conter os segintes :digits digitos.',
    'digits_between' => 'O :attribute deve estar entre :min e :max digitos.',
    'dimensions' => 'O :attribute tem as dimensões de imagem inválidas.',
    'distinct' => 'O :attribute tem um valor duplicado.',
    'email' => 'O :attribute deve ser um endereço de email válido.',
    'exists' => 'O selecionado :attribute é invalido.',
    'file' => 'O :attribute apenas deve ser um ficheiro.',
    'filled' => 'O :attribute é obrigatório preencher.',
    'image' => 'O :attribute apenas deve ser uma imagem.',
    'in' => 'O selecionado :attribute é invalido.',
    'in_array' => 'O :attribute não existe no campo :other.',
    'integer' => 'O :attribute apenas deve conter valores inteiros.',
    'ip' => 'O :attribute deve ser um IP valido.',
    'json' => 'O :attribute deve ser uma STRING em JSON válida.',
    'max' => [
        'numeric' => 'O :attribute não pode ser maior que :max.',
        'file' => 'O :attribute não pode ser maior que :max kilobytes.',
        'string' => 'O :attribute não pode ser maior que :max caracteres.',
        'array' => 'O :attribute não pode ter mais do que :max itens.',
    ],
    'mimes' => 'O :attribute deve ser um arquivo do tipo: :values.',
    'mimetypes' => 'O :attribute deve ser um arquivo do tipo: :values.',
    'min' => [
        'numeric' => 'O :attribute deve ser pelo menos :min.',
        'file' => 'O :attribute deve ter pelo menos :min kilobytes.',
        'string' => 'O :attribute deve ter pelo menos :min caracteres.',
        'array' => 'O :attribute deve ter pelo menos :min itens.',
    ],
    'not_in' => 'O selecionado :attribute é inválido.',
    'numeric' => 'O :attribute deve ser um número.',
    'present' => 'O :attribute campo deve estar presente.',
    'regex' => 'O :attribute formato é inválido.',
    'required' => 'O :attribute campo é obrigatório.',
    'required_if' => 'O :attribute campo é obrigatório :other é :value.',
    'required_unless' => 'O :attribute campo é obrigatório a menos que :other esteja em :values.',
    'required_with' => 'O :attribute campo é nescessario quando :values está presente.',
    'required_with_all' => 'O :attribute campo é obrigatório quando :values esta presente.',
    'required_without' => 'O :attribute campo é obrigatório quando :values não esta presente.',
    'required_without_all' => 'O :attribute campo é obrigatório quando nenhum dos :values esta presente.',
    'same' => 'O :attribute e :other devem ser iguais.',
    'size' => [
        'numeric' => 'O :attribute apenas deve ter :size.',
        'file' => 'O :attribute apenas deve ter :size kilobytes.',
        'string' => 'O :attribute apenas deve ter :size caracters.',
        'array' => 'O :attribute apenas deve conter o numero de :size itens.',
    ],
    'string' => 'O :attribute deve ser uma string.',
    'timezone' => 'A :attribute apenas deve ser uma TimeZone válida.',
    'unique' => 'O :attribute ja foi usado.',
    'uploaded' => 'O :attribute falhou ao ser carregado.',
    'url' => 'O :attribute formato é inválido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

    // Internal validation logic for Pterodactyl
    'internal' => [
        'variable_value' => ':env variável',
        'invalid_password' => 'A senha inserida é inválida ou não corresponde a esta conta.',
    ],
];
