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

    'accepted' => ':attribute muss akzeptiert werden.',
    'active_url' => ':attribute ist keine gültige URL.',
    'after' => ':attribute muss ein Datum nach :date sein.',
    'after_or_equal' => ':attribute muss ein Datum nach oder gleichzeitig zu :date sein.',
    'alpha' => ':attribute darf nur Buchstaben enthalten.',
    'alpha_dash' => ':attribute darf nur Buchstaben, Zahlen und Striche enthalten',
    'alpha_num' => ':attribute darf nur Buchstaben und Zahlen enthalten.',
    'array' => ':attribute muss ein Array sein.',
    'before' => ':attribute muss ein Datum vor :date sein.',
    'before_or_equal' => ':attribute muss ein Datum vor oder gleichzeitig zu :date sein.',
    'between' => [
        'numeric' => ':attribute muss zwischen :min und :max sein.',
        'file' => ':attribute mus zwischen :min und :max kb groß sein.',
        'string' => ':attribute muss zwischen :min und :max Zeichen lang sein.',
        'array' => ':attribute muss zwischen :min und :max Reihen haben.',
    ],
    'boolean' => ':attribute muss wahr oder falsch sein.',
    'confirmed' => ':attribute stimmt nicht mit der Bestätigung überein.',
    'date' => ':attribute ist kein gültiges Datum.',
    'date_format' => ':attribute entspricht nicht dem geforderten Format: :format.',
    'different' => ':attribute und :other müssen unterschiedlich sein.',
    'digits' => ':attribute muss :digits Stellen haben.',
    'digits_between' => ':attribute muss zwischen :min und :max Stellen haben.',
    'dimensions' => 'Die Bildgröße von :attribute ist falsch.',
    'distinct' => 'Das :attribute Feld hat einen ungültigen Wert.',
    'email' => ':attribute muss eine gültige E-Mail Adresse sein.',
    'exists' => 'Das ausgewählte Feld :attribute ist ungültig.',
    'file' => ':attribute muss eine Datei sein.',
    'filled' => 'Das :attribute Feld ist erforderlich.',
    'image' => ':attribute muss ein Bild sein.',
    'in' => 'Der ausgewählte :attribute ist ungültig.',
    'in_array' => 'Die Reihe :attribute existiert nicht in :other.',
    'integer' => ':attribute muss ein Integer sein.',
    'ip' => ':attribute muss eine gültige IP-Adresse sein.',
    'json' => ':attribute muss ein gültiger JSON-String sein.',
    'max' => [
        'numeric' => ':attribute darf nicht größer als :max sein.',
        'file' => ':attribute darf nicht größer als :max KB sein.',
        'string' => ':attribute darf nicht länger als :max Zeichen sein.',
        'array' => ':attribute darf nicht mehr als :max Reihen haben.',
    ],
    'mimes' => ':attribute muss dem folgenden Dateityp entsprechen: :values.',
    'mimetypes' => ':attribute muss einem der folgenden Dateitypen entsprechen: :values.',
    'min' => [
        'numeric' => ':attribute muss mindestens :min sein.',
        'file' => ':attribute muss mindestens :min KB groß sein.',
        'string' => ':attribute muss mindestens :min Zeichen lang sein.',
        'array' => ':attribute muss mindestens :min Reihen haben.',
    ],
    'not_in' => 'Das ausgewählte :attribute ist ungültig.',
    'numeric' => ':attribute muss eine Zahl sein.',
    'present' => ':attribute Feld muss angegeben werden.',
    'regex' => ':attribute Format ist ungültig.',
    'required' => 'Das :attribute Feld ist erforderlich.',
    'required_if' => 'Das :attribute Feld ist erforderlich wenn :other :value ist.',
    'required_unless' => 'Das :attribute Feld ist erforderlich, es sei denn :other ist :values.',
    'required_with' => 'Das :attribute Feld ist erforderlich wenn eine:values angegeben wurde.',
    'required_with_all' => 'Das :attribute Feld ist erforderlich wenn :values angegeben wurde.',
    'required_without' => 'Das :attribute Feld ist erforderlich wenn kein :values angegeben wurde.',
    'required_without_all' => 'Das :attribute Feld ist erforderlich wenn keine :values angegeben wurde.',
    'same' => ':attribute und :other müssen übereinstimmen.',
    'size' => [
        'numeric' => ':attribute muss :size Stellen haben.',
        'file' => ':attribute muss :size KB groß sein.',
        'string' => ':attribute muss :size Zeichen lang sein.',
        'array' => ':attribute muss :size Reihen haben.',
    ],
    'string' => ':attribute muss eine Zeichenkette sein.',
    'timezone' => ':attribute muss eine gültige Zeitzone sein.',
    'unique' => ':attribute wurde bereits genutzt.',
    'uploaded' => ':attribute konnte nicht hochgeladen werden.',
    'url' => ':attribute - das Format ist ungültig.',

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
        'invalid_password' => 'Das angegebene Passwort ist für diesen Account ungültig.',
    ],
];
