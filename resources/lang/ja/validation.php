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

    'accepted' => ':attributeを受け入れる必要があります。',
    'active_url' => ':attributeは有効なURLである必要があります。',
    'after' => ':attributeは:dateより後の日付である必要があります。',
    'after_or_equal' => ':attributeは:dateより後もしくは等しい日付である必要があります。',
    'alpha' => ':attributeは文字のみである必要があります。',
    //TODO Dashes can be translated into various meanings. This should be changed according to the actual use.
    'alpha_dash' => ':attributeは文字、数字、伸ばし棒のみである必要があります。',
    'alpha_num' => ':attributeは文字と数字のみである必要があります。',
    'array' => ':attributeは配列である必要があります。',
    'before' => ':attributeは:dateより前の日付である必要があります。',
    'before_or_equal' => ':attributeは:dateより前もしくは等しい日付である必要があります。',
    'between' => [
        'numeric' => ':attributeは:minから:maxの間である必要があります。',
        'file' => ':attributeは:minkBから:maxkBの間である必要があります。',
        'string' => ':attributeは:min文字から:max文字の間である必要があります。',
        'array' => ':attributeは:min項目から:max項目の間である必要があります。',
    ],
    'boolean' => ':attributeはtrueもしくはfalseである必要があります。',
    'confirmed' => ':attributeは確認と一致する必要があります。',
    'date' => ':attributeは有効な日付である必要があります。',
    'date_format' => ':attributeは:formatに沿っている必要があります。',
    'different' => ':attributeと:otherは異なる必要があります。',
    'digits' => ':attributeは:digits文字である必要があります。',
    'digits_between' => ':attributeは:minから:maxまでの数字である必要があります。',
    'dimensions' => ':attributeは有効なサイズの画像である必要があります。',
    'distinct' => ':attributeは重複しない必要があります。',
    'email' => ':attributeは有効なメールアドレスである必要があります。',
    'exists' => '選択した:attributeが有効である必要があります。',
    'file' => ':attributeはファイルである必要があります。',
    'filled' => ':attributeに値を入力する必要があります。',
    'image' => ':attributeは画像である必要があります。',
    'in' => '選択した:attributeが有効である必要があります。',
    'in_array' => ':attributeは:otherに存在する必要があります。',
    'integer' => ':attributeは数値である必要があります。',
    'ip' => ':attributeは有効なIPアドレスである必要があります。',
    'json' => ':attributeは有効なJSON文字列である必要があります。',
    'max' => [
        'numeric' => ':attributeは:maxを超えない必要があります。',
        'file' => ':attributeは:maxkBを超えない必要があります。',
        'string' => ':attributeは:max文字を超えない必要があります。',
        'array' => ':attributeは:max項目を超えない必要があります。',
    ],
    'mimes' => ':attributeは:valuesファイルである必要があります。',
    'mimetypes' => ':attributeは:valueファイルタイプである必要があります。',
    'min' => [
        'numeric' => ':attributeは少なくとも:minである必要があります。',
        'file' => ':attributeは少なくとも:minkBである必要があります。',
        'string' => ':attributeは少なくとも:min文字である必要があります。',
        'array' => ':attributeは少なくとも:max項目である必要があります。',
    ],
    'not_in' => '選択した:attributeが有効である必要があります。',
    'numeric' => ':attributeは数値である必要があります。',
    'present' => ':attributeが存在している必要があります。',
    'regex' => ':attributeの形式は有効である必要があります。',
    'required' => ':attributeは必ず入力する必要があります。',
    'required_if' => ':otherが:valueの場合、:attributeは必ず入力する必要があります。',
    'required_unless' => ':otherが:valuesにない限り、:attributeは必ず入力する必要があります。。',
    'required_with' => ':valuesが存在する場合、:attributeは必ず入力する必要があります。',
    'required_with_all' => ':valuesが存在する場合、:attributeは必ず入力する必要があります。。',
    'required_without' => ':valuesが存在しない場合、:attributeは必ず入力する必要があります。',
    'required_without_all' => ':valuseが存在しない場合、:attributeは必ず入力する必要があります。',
    'same' => ':attributeと:otherは一致する必要があります。',
    'size' => [
        'numeric' => ':attributeは:sizeである必要があります。',
        'file' => ':attributeは:sizekBである必要があります。',
        'string' => ':attributeは:size文字である必要があります。',
        'array' => ':attributeは:size項目である必要があります。',
    ],
    'string' => ':attributeは文字列である必要があります。',
    'timezone' => ':attributeは有効なゾーンである必要があります。',
    'unique' => ':attributeはすでに使用されていない必要があります。',
    'uploaded' => ':attributeをアップロードできませんでした。',
    'url' => ':attributeの形式は有効である必要があります。',

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
        'variable_value' => ':env変数',
    ],
];
