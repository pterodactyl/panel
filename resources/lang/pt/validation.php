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
    'accepted'             => 'O :attribute precisa ser aceito.',
    'active_url'           => 'O :attribute não é uma URL válida.',
    'after'                => 'O :attribute precisa ser uma data após :date.',
    'alpha'                => 'O :attribute só pode conter letras.',
    'alpha_dash'           => 'O :attribute pode apenas conter letras, números, e hífens.',
    'alpha_num'            => 'O :attribute pode apenas conter letras e números.',
    'array'                => 'O :attribute precisa ser uma lista.',
    'before'               => 'O :attribute precisa ser uma data antes de :date.',
    'between'              => [
        'numeric' => 'O :attribute precisa estar entre :min e :max.',
        'file'    => 'O :attribute precisa estar entre :min e :max kilobytes.',
        'string'  => 'O :attribute precisa estar entre :min e :max caracteres.',
        'array'   => 'O :attribute precisa estar entre :min e :max itens.',
    ],
    'boolean'              => 'O :attribute precisa ser falso ou verdadeiro.',
    'confirmed'            => 'O :attribute de confirmação não combina.',
    'date'                 => 'O :attribute não é uma data válida.',
    'date_format'          => 'O :attribute não combina com o formato :format.',
    'different'            => 'O :attribute e :other precisam ser diferentes.',
    'digits'               => 'O :attribute precisa ter :digits dígitos.',
    'digits_between'       => 'O :attribute precisa estar entre :min e :max dígitos.',
    'email'                => 'O :attribute precisa ser um endereço de email válido.',
    'exists'               => 'O :attribute selecionado é inválido.',
    'filled'               => 'O campo :attribute é obrigatório.',
    'image'                => 'O :attribute precisa ser uma imagem.',
    'in'                   => 'O :attribute é inválido.',
    'integer'              => 'O :attribute precisa ser um número inteiro.',
    'ip'                   => 'O :attribute precisa ser um endereço IP válido.',
    'json'                 => 'O :attribute precia ser um texto JSON válido.',
    'max'                  => [
        'numeric' => 'O :attribute não pode ser maior que :max.',
        'file'    => 'O :attribute não pode ser maior que :max kilobytes.',
        'string'  => 'O :attribute não pode ter mais do que :max caracteres.',
        'array'   => 'O :attribute não pode ter mais do que :max itens.',
    ],
    'mimes'                => 'O :attribute precisa ser um arquivo do tipo: :values.',
    'min'                  => [
        'numeric' => 'O :attribute precisa ser ao menos :min.',
        'file'    => 'O :attribute precisa ser ao menos :min kilobytes.',
        'string'  => 'O :attribute precisa ter ao menos :min caracteres.',
        'array'   => 'O :attribute precisa ter ao menos :min itens.',
    ],
    'not_in'               => 'O :attribute selecionado é inválido.',
    'numeric'              => 'O :attribute precisa ser um número.',
    'regex'                => 'O formato de :attribute é inválido.',
    'required'             => 'O campo :attribute é obrigatório.',
    'required_if'          => 'O campo :attribute é obrigatório quando :other é :value.',
    'required_with'        => 'O campo :attribute é obrigatório quando :values está presente.',
    'required_with_all'    => 'O campo :attribute é obrigatório quando :values estão presentes.',
    'required_without'     => 'O campo :attribute é obrigatório quando :values não estão presentes.',
    'required_without_all' => 'O campo :attribute é obrigatório quando nenhum de :values estão presentes.',
    'same'                 => 'O campo :attribute e :other precisam combinar.',
    'size'                 => [
        'numeric' => 'O :attribute precisa ser :size.',
        'file'    => 'O :attribute precisa ser :size kilobytes.',
        'string'  => 'O :attribute precisa ser :size caracteres.',
        'array'   => 'O :attribute precisa conter :size itens.',
    ],
    'string'               => 'O :attribute precisa ser um texto.',
    'totp'                 => 'O token TOTP é inválido. Ele expirou?',
    'timezone'             => 'O :attribute precisa ser um fuso horário válido.',
    'unique'               => 'O :attribute já foi pego.',
    'url'                  => 'O formato de :attribute é inválido.',
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */
    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],
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
];
