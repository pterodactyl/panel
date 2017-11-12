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

    'accepted'             => ':attribute kabul edilmelidir.',
    'active_url'           => ':attribute geçerli bir URL değil.',
    'after'                => ':attribute :date tarihinden ileri bir tarih olmalıdır.',
    'after_or_equal'       => ':attribute :date tarihine eşit ya da daha ileri bir tarih olmalıdır.',
    'alpha'                => ':attribute sadece harften oluşmalıdır.',
    'alpha_dash'           => ':attribute sadece harf, sayı ve "-" içermelidir.',
    'alpha_num'            => ':attribute sadece harf ve sayı içermelidir.',
    'array'                => ':attribute dizgi olmalıdır.',
    'before'               => ':attribute :date tarihinden önce bir tarih olmalıdır.',
    'before_or_equal'      => ':attribute :date tarihine eşit ya da daha öncesi bir tarih olmalıdır.',
    'between'              => [
        'numeric' => ':attribute :min ile :max arasında olmalıdır.',
        'file'    => ':attribute :min kb ile :max kb arasında olmalıdır.',
        'string'  => ':attribute :min ile :max karakter uzunluğu arasında olmalıdır.',
        'array'   => ':attribute :min ile :max arasında bir miktar kadar içeriğe sahip olmalıdır.',
    ],
    'boolean'              => ':attribute true ya da false olmalıdır.',
    'confirmed'            => ':attribute onay uyuşmuyor.',
    'date'                 => ':attribute geçerli bir tarih değil.',
    'date_format'          => ':attribute formatına uygun değil: :format.',
    'different'            => ':attribute :other olmamalıdır.',
    'digits'               => ':attribute :digits hane olmalıdır.',
    'digits_between'       => ':attribute :min ile :max hane arasında olmalıdır.',
    'dimensions'           => ':attribute uygunsuz resim boyutlarına sahiptir.',
    'distinct'             => ':attribute alanı eşsiz olmalıdır.',
    'email'                => ':attribute geçerli bir email adresi olmalıdır.',
    'exists'               => 'seçili :attribute geçersizdir.',
    'file'                 => ':attribute dosya olmalıdır.',
    'filled'               => ':attribute gereklir bir alandır.',
    'image'                => ':attribute resim olmalıdır.',
    'in'                   => 'seçili :attribute geçersizdir.',
    'in_array'             => ':attribute alanı :other içinde bulunmamaktadır.',
    'integer'              => ':attribute tam sayı olmalıdır.',
    'ip'                   => ':attribute geçerli bir IP adresi olmalıdır.',
    'json'                 => ':attribute geçerli bir JSON dizisi olmalıdır.',
    'max'                  => [
        'numeric' => ':attribute :max sayısından büyük olamaz.',
        'file'    => ':attribute :max kb\'dan büyük olamaz.',
        'string'  => ':attribute :max karakterden uzun olamaz.',
        'array'   => ':attribute :max\'dan fazla içeriğe sahip olamaz.',
    ],
    'mimes'                => ':attribute dosya tipi olarak şunlardan biri olmalıdır: :values.',
    'mimetypes'            => ':attribute dosya tipi olarak şunlardan biri olmalıdır: :values.',
    'min'                  => [
        'numeric' => ':attribute en az :min olmalıdır.',
        'file'    => ':attribute en az :min kb olmalıdır.',
        'string'  => ':attribute en az :min karater içermelidir.',
        'array'   => ':attribute en az :min içeriğe sahip olmalıdır.',
    ],
    'not_in'               => 'seçili :attribute geçersiz.',
    'numeric'              => ':attribute sayı olmalıdır.',
    'present'              => ':attribute alanı mevcut olmalıdır.',
    'regex'                => ':attribute hatalı format.',
    'required'             => ':attribute alanı gereklidir.',
    'required_if'          => ':attribute alanı :other değeri :value olduğunda gereklidir.',
    'required_unless'      => ':attribute alanı :other değeri :values olmadığı sürece gereklidir.',
    'required_with'        => ':attribute alanı :values mevcut ise zorunludur.',
    'required_with_all'    => ':attribute alanı :values mevcut ise zorunludur.',
    'required_without'     => ':attribute alanı :values mevcut değil ise zorunludur.',
    'required_without_all' => ':attribute alanı :values değerlerinin hiç biri mevcut değil ise zorunludur.',
    'same'                 => ':attribute ve :other aynı olmalılar.',
    'size'                 => [
        'numeric' => ':attribute :size olmalıdır.',
        'file'    => ':attribute :size kb olmalıdır.',
        'string'  => ':attribute :size karakter olmalıdır.',
        'array'   => ':attribute :size içeriğe sahip olmalıdır.',
    ],
    'string'               => ':attribute dizi olmalıdır.',
    'timezone'             => ':attribute geçerli bir zaman dilimi olmalıdır.',
    'unique'               => ':attribute zaten mevcut.',
    'uploaded'             => ':attribute yükleme başarısız.',
    'url'                  => ':attribute geçersiz format.',

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
