<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validierungs-Sprachzeilen
    |--------------------------------------------------------------------------
    |
    | Die folgenden Sprachzeilen enthalten die Standard-Fehlermeldungen
    | der Validierungsklasse.
    |
    */

    'accepted' => ':Attribute muss akzeptiert werden.',
    'accepted_if' => ':Attribute muss akzeptiert werden, wenn :other :value ist.',
    'active_url' => ':Attribute ist keine gültige URL.',
    'after' => ':Attribute muss ein Datum nach :date sein.',
    'after_or_equal' => ':Attribute muss ein Datum nach oder gleich :date sein.',
    'alpha' => ':Attribute darf nur Buchstaben enthalten.',
    'alpha_dash' => ':Attribute darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten.',
    'alpha_num' => ':Attribute darf nur Buchstaben und Zahlen enthalten.',
    'array' => ':Attribute muss ein Array sein.',
    'ascii' => ':Attribute darf nur alphanumerische Einzelbyte-Zeichen und Symbole enthalten.',
    'before' => ':Attribute muss ein Datum vor :date sein.',
    'before_or_equal' => ':Attribute muss ein Datum vor oder gleich :date sein.',
    'between' => [
        'array' => ':Attribute muss zwischen :min und :max Einträge haben.',
        'file' => ':Attribute muss zwischen :min und :max Kilobyte groß sein.',
        'numeric' => ':Attribute muss zwischen :min und :max liegen.',
        'string' => ':Attribute muss zwischen :min und :max Zeichen lang sein.',
    ],
    'boolean' => ':Attribute muss wahr oder falsch sein.',
    'can' => ':Attribute enthält einen nicht autorisierten Wert.',
    'confirmed' => 'Die Bestätigung von :attribute stimmt nicht überein.',
    'contains' => ':Attribute fehlt ein erforderlicher Wert.',
    'current_password' => 'Das Passwort ist falsch.',
    'date' => ':Attribute ist kein gültiges Datum.',
    'date_equals' => ':Attribute muss ein Datum gleich :date sein.',
    'date_format' => ':Attribute muss dem Format :format entsprechen.',
    'decimal' => ':Attribute muss :decimal Dezimalstellen haben.',
    'declined' => ':Attribute muss abgelehnt werden.',
    'declined_if' => ':Attribute muss abgelehnt werden, wenn :other :value ist.',
    'different' => ':Attribute und :other müssen sich unterscheiden.',
    'digits' => ':Attribute muss :digits Ziffern haben.',
    'digits_between' => ':Attribute muss zwischen :min und :max Ziffern haben.',
    'dimensions' => ':Attribute hat ungültige Bildabmessungen.',
    'distinct' => ':Attribute hat einen doppelten Wert.',
    'doesnt_end_with' => ':Attribute darf nicht mit einem der folgenden Werte enden: :values.',
    'doesnt_start_with' => ':Attribute darf nicht mit einem der folgenden Werte beginnen: :values.',
    'email' => ':Attribute muss eine gültige E-Mail-Adresse sein.',
    'ends_with' => ':Attribute muss mit einem der folgenden Werte enden: :values.',
    'enum' => 'Der gewählte Wert für :attribute ist ungültig.',
    'exists' => 'Der gewählte Wert für :attribute ist ungültig.',
    'extensions' => ':Attribute muss eine der folgenden Dateiendungen haben: :values.',
    'file' => ':Attribute muss eine Datei sein.',
    'filled' => ':Attribute muss einen Wert haben.',
    'gt' => [
        'array' => ':Attribute muss mehr als :value Einträge haben.',
        'file' => ':Attribute muss größer als :value Kilobyte sein.',
        'numeric' => ':Attribute muss größer als :value sein.',
        'string' => ':Attribute muss mehr als :value Zeichen haben.',
    ],
    'gte' => [
        'array' => ':Attribute muss :value oder mehr Einträge haben.',
        'file' => ':Attribute muss größer oder gleich :value Kilobyte sein.',
        'numeric' => ':Attribute muss größer oder gleich :value sein.',
        'string' => ':Attribute muss mindestens :value Zeichen haben.',
    ],
    'hex_color' => ':Attribute muss eine gültige Hexadezimalfarbe sein.',
    'image' => ':Attribute muss ein Bild sein.',
    'in' => 'Der gewählte Wert für :attribute ist ungültig.',
    'in_array' => ':Attribute muss in :other vorhanden sein.',
    'integer' => ':Attribute muss eine Ganzzahl sein.',
    'ip' => ':Attribute muss eine gültige IP-Adresse sein.',
    'ipv4' => ':Attribute muss eine gültige IPv4-Adresse sein.',
    'ipv6' => ':Attribute muss eine gültige IPv6-Adresse sein.',
    'json' => ':Attribute muss ein gültiger JSON-String sein.',
    'list' => ':Attribute muss eine Liste sein.',
    'lowercase' => ':Attribute muss in Kleinbuchstaben sein.',
    'lt' => [
        'array' => ':Attribute muss weniger als :value Einträge haben.',
        'file' => ':Attribute muss kleiner als :value Kilobyte sein.',
        'numeric' => ':Attribute muss kleiner als :value sein.',
        'string' => ':Attribute muss weniger als :value Zeichen haben.',
    ],
    'lte' => [
        'array' => ':Attribute darf nicht mehr als :value Einträge haben.',
        'file' => ':Attribute darf nicht größer als :value Kilobyte sein.',
        'numeric' => ':Attribute darf nicht größer als :value sein.',
        'string' => ':Attribute darf nicht mehr als :value Zeichen haben.',
    ],
    'mac_address' => ':Attribute muss eine gültige MAC-Adresse sein.',
    'max' => [
        'array' => ':Attribute darf nicht mehr als :max Einträge haben.',
        'file' => ':Attribute darf nicht größer als :max Kilobyte sein.',
        'numeric' => ':Attribute darf nicht größer als :max sein.',
        'string' => ':Attribute darf nicht mehr als :max Zeichen haben.',
    ],
    'max_digits' => ':Attribute darf nicht mehr als :max Ziffern haben.',
    'mimes' => ':Attribute muss eine Datei vom Typ :values sein.',
    'mimetypes' => ':Attribute muss eine Datei vom Typ :values sein.',
    'min' => [
        'array' => ':Attribute muss mindestens :min Einträge haben.',
        'file' => ':Attribute muss mindestens :min Kilobyte groß sein.',
        'numeric' => ':Attribute muss mindestens :min sein.',
        'string' => ':Attribute muss mindestens :min Zeichen lang sein.',
    ],
    'min_digits' => ':Attribute muss mindestens :min Ziffern haben.',
    'missing' => ':Attribute darf nicht vorhanden sein.',
    'missing_if' => ':Attribute darf nicht vorhanden sein, wenn :other :value ist.',
    'missing_unless' => ':Attribute darf nicht vorhanden sein, es sei denn :other ist :value.',
    'missing_with' => ':Attribute darf nicht vorhanden sein, wenn :values vorhanden ist.',
    'missing_with_all' => ':Attribute darf nicht vorhanden sein, wenn :values vorhanden sind.',
    'multiple_of' => ':Attribute muss ein Vielfaches von :value sein.',
    'not_in' => 'Der gewählte Wert für :attribute ist ungültig.',
    'not_regex' => ':Attribute hat ein ungültiges Format.',
    'numeric' => ':Attribute muss eine Zahl sein.',
    'password' => [
        'letters' => ':Attribute muss mindestens einen Buchstaben enthalten.',
        'mixed' => ':Attribute muss mindestens einen Großbuchstaben und einen Kleinbuchstaben enthalten.',
        'numbers' => ':Attribute muss mindestens eine Zahl enthalten.',
        'symbols' => ':Attribute muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => ':Attribute wurde in einem Datenleck gefunden. Bitte wählen Sie ein anderes :attribute.',
    ],
    'present' => ':Attribute muss vorhanden sein.',
    'present_if' => ':Attribute muss vorhanden sein, wenn :other :value ist.',
    'present_unless' => ':Attribute muss vorhanden sein, es sei denn :other ist :value.',
    'present_with' => ':Attribute muss vorhanden sein, wenn :values vorhanden ist.',
    'present_with_all' => ':Attribute muss vorhanden sein, wenn :values vorhanden sind.',
    'prohibited' => ':Attribute ist nicht erlaubt.',
    'prohibited_if' => ':Attribute ist nicht erlaubt, wenn :other :value ist.',
    'prohibited_unless' => ':Attribute ist nicht erlaubt, es sei denn :other ist in :values.',
    'prohibits' => ':Attribute verbietet die Anwesenheit von :other.',
    'regex' => ':Attribute hat ein ungültiges Format.',
    'required' => ':Attribute ist erforderlich.',
    'required_array_keys' => ':Attribute muss Einträge für :values enthalten.',
    'required_if' => ':Attribute ist erforderlich, wenn :other :value ist.',
    'required_if_accepted' => ':Attribute ist erforderlich, wenn :other akzeptiert ist.',
    'required_if_declined' => ':Attribute ist erforderlich, wenn :other abgelehnt ist.',
    'required_unless' => ':Attribute ist erforderlich, es sei denn :other ist in :values.',
    'required_with' => ':Attribute ist erforderlich, wenn :values vorhanden ist.',
    'required_with_all' => ':Attribute ist erforderlich, wenn :values vorhanden sind.',
    'required_without' => ':Attribute ist erforderlich, wenn :values nicht vorhanden ist.',
    'required_without_all' => ':Attribute ist erforderlich, wenn keiner der Werte :values vorhanden ist.',
    'same' => ':Attribute und :other müssen übereinstimmen.',
    'size' => [
        'array' => ':Attribute muss :size Einträge enthalten.',
        'file' => ':Attribute muss :size Kilobyte groß sein.',
        'numeric' => ':Attribute muss :size sein.',
        'string' => ':Attribute muss :size Zeichen lang sein.',
    ],
    'starts_with' => ':Attribute muss mit einem der folgenden Werte beginnen: :values.',
    'string' => ':Attribute muss eine Zeichenkette sein.',
    'timezone' => ':Attribute muss eine gültige Zeitzone sein.',
    'unique' => ':Attribute ist bereits vergeben.',
    'uploaded' => ':Attribute konnte nicht hochgeladen werden.',
    'uppercase' => ':Attribute muss in Großbuchstaben sein.',
    'url' => ':Attribute muss eine gültige URL sein.',
    'ulid' => ':Attribute muss eine gültige ULID sein.',
    'uuid' => ':Attribute muss eine gültige UUID sein.',

    /*
    |--------------------------------------------------------------------------
    | Benutzerdefinierte Validierungsmeldungen
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Benutzerdefinierte Validierungsattribute
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'Name',
        'email' => 'E-Mail-Adresse',
        'password' => 'Passwort',
        'password_confirmation' => 'Passwort-Bestätigung',
    ],

];
