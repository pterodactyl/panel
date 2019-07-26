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

    'accepted' => ':attribute doit-être accepté.',
    'active_url' => ":attribute n'est pas une URL valide.",
    'after' => 'Le :attribute dois être une date après le :date ',
    'after_or_equal' => 'Le :attribut dois être une date supérieure ou égale au :date.',
    'alpha' => ':attribute doit uniquement contenir des lettres.',
    'alpha_dash' => ':attribute doit uniquement contenir des lettres, chiffres et tirets.',
    'alpha_num' => ':attribute doit uniquement contenir des lettres et chiffres.',
    'array' => 'Le :attribute dois être un tableau.',
    'before' => ':attribute doit être une date avant :date.',
    'before_or_equal' => 'Le :attribute dois être une date avant ou égale à :date.',
    'between' => [
        'array' => 'Le :attribute dois avoir entre :min et :max éléments.',
        'file' => 'Le :attribute dois compris entre :min et :max kilobytes.',
        'numeric' => ':attribute doit être entre :min et :max.',
        'string' => 'Le :attribute dois être compris entre :min et :max caractères.',
    ],
    'boolean' => 'Le champ :attribute doit être <code>true</code> or <code>false</code>.',
    'confirmed' => 'La confirmation :attribute ne correspond pas.',
    'date' => "Le :attribute n'est pas une date valide.",
    'date_format' => ':attribute ne correspond pas au format :format.',
    'different' => 'Le :attribute et :other doivent êtres différents.',
    'digits' => 'Le :attribute dois comprendre :digits chiffres.',
    'digits_between' => 'Le :attribute dois être compris entre :min et :max chiffres.',
    'dimensions' => "Le :attribute as une taille d'image invalide.",
    'distinct' => 'Le champ :attribute a une valeur dupliquée.',
    'email' => 'Le :attribute doit être une adresse E-Mail valide.',
    'exists' => 'Le :attribute est invalide.',
    'file' => 'Le :attribute dois être un fichier.',
    'filled' => 'Le champ :attribute est obligatoire.',
    'image' => 'Le :attribute dois être une image.',
    'in' => 'Le :attribute selectionné est invalide.',
    'in_array' => 'Le champ :attribute n\'existe pas dans :other.',
    'integer' => 'Le :attribute doit être un entier.',
    'ip' => ':attribute doit être une adresse IP valide.',
    'json' => 'Le :attribute doit être une chaîne de caractère JSON valide.',
    'max' => [
        'numeric' => ':attribute ne doit pas être supérieur à :max.',
        'file' => ':attribute ne doit pas dépasser :max kilooctets.',
        'string' => 'Le :attribute ne peut pas être supérieur à :max caractères.',
        'array' => 'Le :attribute ne peuvent avoir plus de :max éléments',
    ],
    'mimes' => 'Le :attribute dois être un fichier de type :values.',
    'mimetypes' => 'Le :attribute dois être un fichier de type :values.',
    'min' => [
        'numeric' => ":attribute doit être d'au moins :min.",
        'file' => ":attribute doit être d'au moins :min kilooctets.",
        'string' => 'Le :attribute dois avoir moins de :min caractères.',
        'array' => 'Le :attribute dois avoir au minimum :min éléments.',
    ],
    'not_in' => 'Le :attribute selectionné est invalide.',
    'numeric' => 'Le :attribute dois être un nombre.',
    'present' => 'Le champ :attribute dois être remplis.',
    'regex' => 'Le format de :attribute est invalide.',
    'required' => 'Le champ :attribute est requis.',
    'required_if' => 'Le champ :attribute est requis quand :other est à :value.',
    'required_unless' => 'Le champ :attribute est requis a part si :other est dans :values.',
    'required_with' => 'Le champ :attribute est requis quand :values est présent.',
    'required_with_all' => 'Le champ :attribute est requis quand :values est présent.',
    'required_without' => 'Le champ :attribute est requis quand :values n\'est pas présent.',
    'required_without_all' => "Lorsque :values n'est/ne sont pas présent(s), le champ :attribute est requis.",
    'same' => ':attribute et :other doivent correspondres.',
    'size' => [
        'numeric' => ':attribute doit être de :size.',
        'file' => ':attribute doit faire :size kilooctets.',
        'string' => ':attribute dois contenir :size caractères.',
        'array' => 'Le :attribute dois contenir :size éléments.',
    ],
    'string' => 'Le :attribute doit être une chaîne de caractère.',
    'timezone' => ':attribute doit être une zone valide.',
    'unique' => ':attribute a déjà été pris.',
    'uploaded' => 'Le :attribute n\'a pas réussi à télécharger.',
    'url' => 'Le formant d\':attribute est invalide.',

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
        'variable_value' => ':env variable',
    ],
];
