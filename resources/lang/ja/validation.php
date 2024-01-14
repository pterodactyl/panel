<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 検証言語行
    |--------------------------------------------------------------------------
    |
    | 以下の言語行は、バリデータクラスによって使用されるデフォルトのエラーメッセージを含んでいます。
    | これらのルールの一部には、サイズルールなどの複数のバージョンがあります。
    | ここでこれらのメッセージを自由に調整してください。
    |
    */

    'accepted' => ':attributeを承認してください',
    'active_url' => ':attributeは有効なURLではありません',
    'after' => ':attributeは:date以降の日付でなければなりません',
    'after_or_equal' => ':attributeは:date以降の日付でなければなりません',
    'alpha' => ':attributeは文字のみを含むことができます',
    'alpha_dash' => ':attributeは文字、数字、ダッシュのみを含むことができます',
    'alpha_num' => ':attributeは文字と数字のみを含むことができます',
    'array' => ':attributeは配列でなければなりません',
    'before' => ':attributeは:dateより前の日付でなければなりません',
    'before_or_equal' => ':attributeは:date以前の日付でなければなりません',
    'between' => [
        'numeric' => ':attributeは:minから:maxの間でなければなりません',
        'file' => ':attributeは:minから:maxキロバイトの間でなければなりません',
        'string' => ':attributeは:minから:max文字の間でなければなりません',
        'array' => ':attributeは:minから:max項目の間でなければなりません',
    ],
    'boolean' => ':attributeフィールドは真または偽でなければなりません',
    'confirmed' => ':attributeの確認が一致しません',
    'date' => ':attributeは有効な日付ではありません',
    'date_format' => ':attributeは:format形式と一致しません',
    'different' => ':attributeと:otherは異なっていなければなりません',
    'digits' => ':attributeは:digits桁でなければなりません',
    'digits_between' => ':attributeは:minから:max桁の間でなければなりません',
    'dimensions' => ':attributeは無効な画像サイズです',
    'distinct' => ':attributeフィールドには重複した値があります',
    'email' => ':attributeは有効なメールアドレスでなければなりません',
    'exists' => '選択された:attributeは無効です',
    'file' => ':attributeはファイルでなければなりません',
    'filled' => ':attributeフィールドは必須です',
    'image' => ':attributeは画像でなければなりません',
    'in' => '選択された:attributeは無効です',
    'in_array' => ':attributeフィールドは:otherに存在しません',
    'integer' => ':attributeは整数でなければなりません',
    'ip' => ':attributeは有効なIPアドレスでなければなりません',
    'json' => ':attributeは有効なJSON文字列でなければなりません',
    'max' => [
        'numeric' => ':attributeは:maxを超えてはなりません',
        'file' => ':attributeは:maxキロバイトを超えてはなりません',
        'string' => ':attributeは:max文字を超えてはなりません',
        'array' => ':attributeは:max項目を超えてはなりません',
    ],
    'mimes' => ':attributeは:valuesタイプのファイルでなければなりません',
    'mimetypes' => ':attributeは:valuesタイプのファイルでなければなりません',
    'min' => [
        'numeric' => ':attributeは少なくとも:minでなければなりません',
        'file' => ':attributeは少なくとも:minキロバイトでなければなりません',
        'string' => ':attributeは少なくとも:min文字でなければなりません',
        'array' => ':attributeは少なくとも:min項目でなければなりません',
    ],
    'not_in' => '選択された:attributeは無効です',
    'numeric' => ':attributeは数値でなければなりません',
    'present' => ':attributeフィールドは存在しなければなりません',
    'regex' => ':attributeの形式は無効です',
    'required' => ':attributeフィールドは必須です',
    'required_if' => ':otherが:valueの場合、:attributeフィールドは必須です',
    'required_unless' => ':otherが:valuesにない限り、:attributeフィールドは必須です',
    'required_with' => ':valuesが存在する場合、:attributeフィールドは必須です',
    'required_with_all' => ':valuesが存在する場合、:attributeフィールドは必須です',
    'required_without' => ':valuesが存在しない場合、:attributeフィールドは必須です',
    'required_without_all' => ':valuesが一つも存在しない場合、:attributeフィールドは必須です',
    'same' => ':attributeと:otherは一致しなければなりません',
    'size' => [
        'numeric' => ':attributeは:sizeでなければなりません',
        'file' => ':attributeは:sizeキロバイトでなければなりません',
        'string' => ':attributeは:size文字でなければなりません',
        'array' => ':attributeは:size項目を含む必要があります',
    ],
    'string' => ':attributeは文字列でなければなりません',
    'timezone' => ':attributeは有効なゾーンでなければなりません',
    'unique' => ':attributeはすでに取得されています',
    'uploaded' => ':attributeのアップロードに失敗しました',
    'url' => ':attributeの形式は無効です',

    /*
    |--------------------------------------------------------------------------
    | カスタム検証属性
    |--------------------------------------------------------------------------
    |
    | 以下の言語行は、属性プレースホルダーを"email"の代わりに
    | Eメールアドレスなど、読みやすいものに置き換えるために使用されます。
    | これは単にメッセージをクリーンにするためのものです。
    |
    */

    'attributes' => [],

    // Pterodactylの内部検証ロジック
    'internal' => [
        'variable_value' => ':env変数',
        'invalid_password' => '提供されたパスワードはこのアカウントでは無効です',
    ],
];

