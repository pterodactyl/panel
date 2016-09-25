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

    'accepted'             => ':attribute muss akzeptiert werden.',
    'active_url'           => ':attribute ist keine gültige URL.',
    'after'                => ':attribute muss ein Datum in diesem Format sein: :date',
    'alpha'                => ':attribute darf nur Buchstaben enthalten.',
    'alpha_dash'           => ':attribute darf nur Buchstaben, Ziffern und Bindestriche enthalten.',
    'alpha_num'            => ':attribute darf nur Zahlen und Buchstaben enthalten.',
    'array'                => ':attribute muss ein Array sein.',
    'before'               => ':attribute muss ein Datum in diesem Format sein: :date',
    'between'              => [
        'numeric' => ':attribute muss zwischen :min und :max liegen.',
        'file'    => ':attribute muss zwischen :min und :max Kilobyte liegen.',
        'string'  => ':attribute muss aus :min bis :max Zeichen bestehen.',
        'array'   => ':attribute muss aus :min bis :max Elemente bestehen.',
    ],
    'boolean'              => ':attribute muss wahr oder falsch sein.',
    'confirmed'            => ':attribute wiederholung stimmt nicht überein.',
    'date'                 => ':attribute ist kein gültiges Datum.',
    'date_format'          => ':attribute entspricht nicht dem Format: :format',
    'different'            => ':attribute und :other muss verschieden sein.',
    'digits'               => ':attribute muss aus :digits Ziffern bestehen.',
    'digits_between'       => ':attribute muss aus :min bis :max Ziffern bestehen.',
    'email'                => ':attribute muss eine gültige Email Adresse sein.',
    'exists'               => 'das ausgewählte Attribut :attribute ist ungültig.',
    'filled'               => ':attribute ist erforderlich.',
    'image'                => ':attribute muss ein Bild sein.',
    'in'                   => 'Das ausgewählte Attribut :attribute ist ungültig.',
    'integer'              => ':attribute muss eine Zahl sein.',
    'ip'                   => ':attribute muss eine gültige IP Adresse sein.',
    'json'                 => ':attribute muss ein gültiger JSON String sein.',
    'max'                  => [
        'numeric' => ':attribute darf nicht größer als :max sein.',
        'file'    => ':attribute darf nicht größer als :max Kilobytes sein.',
        'string'  => ':attribute darf nicht mehr als :max Zeichen lang sein.',
        'array'   => ':attribute darf nicht aus mehr als :max Elementen bestehen.',
    ],
    'mimes'                => ':attribute muss eine Datei des Typs :values sein.',
    'min'                  => [
        'numeric' => ':attribute muss mindestens :min sein.',
        'file'    => ':attribute muss mindestens :min Kilobytes sein.',
        'string'  => ':attribute muss mindestens :min Zeichen lang sein.',
        'array'   => ':attribute muss aus mindestens :min Elementen bestehen.',
    ],
    'not_in'               => 'Das ausgewählte Attribut :attribute ist ungültig.',
    'numeric'              => ':attribute muss eine Zahl sein.',
    'regex'                => ':attribute Format ungültig.',
    'required'             => ':attribute ist erforderlich.',
    'required_if'          => ':attribute ist erforderlich wenn :other gleich :value ist.',
    'required_with'        => ':attribute ist erforderlich wenn eines von :values gesetzt ist.',
    'required_with_all'    => ':attribute ist erforderlich wenn :values gesetzt ist.',
    'required_without'     => ':attribute ist erforderlich wenn :values nicht gesetzt ist.',
    'required_without_all' => ':attribute ist erforderlich wenn keines von :values gesetzt ist.',
    'same'                 => ':attribute und :other müssen übereinstimmen.',
    'size'                 => [
        'numeric' => ':attribute muss :size groß sein.',
        'file'    => ':attribute muss :size Kilobytes groß sein.',
        'string'  => ':attribute muss :size Zeichen lang sein.',
        'array'   => ':attribute muss mindestens :size Elemente enthalten.',
    ],
    'string'               => ':attribute muss eine Zeichenkette sein.',
    'totp'                 => 'totp Token ist ungültig. Ist es abgelaufen?',
    'timezone'             => ':attribute muss eine gültige Zeitzone sein.',
    'unique'               => ':attribute wurde bereits verwendet.',
    'url'                  => ':attribute Format ungültig.',

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
