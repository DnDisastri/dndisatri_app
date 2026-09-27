<?php

declare(strict_types=1);

/*
 * I messaggi di errore della validazione, in italiano.
 *
 * Senza questo file Laravel non trova il testo e stampa la chiave: chi si
 * registrava con una password compromessa leggeva
 * «validation.password.uncompromised» e non aveva modo di capire cosa
 * cambiare.
 *
 * Il codice passa messaggi propri dove la frase generica non basta, e quelli
 * vincono su questi. Qui c'è la rete sotto: ogni regola ha una frase decente
 * anche se nessuno ci ha pensato.
 *
 * `:attribute` è il nome del campo, e lo si può addolcire in `attributes` in
 * fondo al file.
 */

return [
    'accepted' => 'Devi accettare :attribute.',
    'accepted_if' => 'Devi accettare :attribute quando :other è :value.',
    'active_url' => ':attribute non è un indirizzo valido.',
    'after' => ':attribute deve essere una data successiva al :date.',
    'after_or_equal' => ':attribute deve essere il :date o una data successiva.',
    'alpha' => ':attribute può contenere solo lettere.',
    'alpha_dash' => ':attribute può contenere solo lettere, numeri, trattini e trattini bassi.',
    'alpha_num' => ':attribute può contenere solo lettere e numeri.',
    'any_of' => ':attribute non è valido.',
    'array' => ':attribute deve essere un elenco.',
    'ascii' => ':attribute può contenere solo caratteri e simboli semplici.',
    'before' => ':attribute deve essere una data precedente al :date.',
    'before_or_equal' => ':attribute deve essere il :date o una data precedente.',

    'between' => [
        'array' => ':attribute deve avere fra :min e :max elementi.',
        'file' => ':attribute deve pesare fra :min e :max kilobyte.',
        'numeric' => ':attribute deve essere fra :min e :max.',
        'string' => ':attribute deve avere fra :min e :max caratteri.',
    ],

    'boolean' => ':attribute può essere solo vero o falso.',
    'can' => ':attribute contiene un valore non permesso.',
    'confirmed' => ':attribute non coincide con la conferma.',
    'contains' => 'A :attribute manca un valore richiesto.',
    'current_password' => 'La password non è corretta.',
    'date' => ':attribute non è una data valida.',
    'date_equals' => ':attribute deve essere il :date.',
    'date_format' => ':attribute non corrisponde al formato :format.',
    'decimal' => ':attribute deve avere :decimal cifre decimali.',
    'declined' => 'Devi rifiutare :attribute.',
    'declined_if' => 'Devi rifiutare :attribute quando :other è :value.',
    'different' => ':attribute e :other devono essere diversi.',
    'digits' => ':attribute deve avere :digits cifre.',
    'digits_between' => ':attribute deve avere fra :min e :max cifre.',
    'dimensions' => 'Le dimensioni dell\'immagine non vanno bene.',
    'distinct' => ':attribute contiene un valore ripetuto.',
    'doesnt_contain' => ':attribute non deve contenere nessuno di questi valori: :values.',
    'doesnt_end_with' => ':attribute non deve finire con: :values.',
    'doesnt_start_with' => ':attribute non deve iniziare con: :values.',
    'email' => ':attribute non è un indirizzo email valido.',
    'encoding' => ':attribute non usa la codifica :encoding.',
    'ends_with' => ':attribute deve finire con: :values.',
    'enum' => ':attribute non è fra i valori ammessi.',
    'exists' => ':attribute non esiste.',
    'extensions' => ':attribute deve essere un file di tipo: :values.',
    'file' => ':attribute deve essere un file.',
    'filled' => ':attribute non può restare vuota.',

    'gt' => [
        'array' => ':attribute deve avere più di :value elementi.',
        'file' => ':attribute deve pesare più di :value kilobyte.',
        'numeric' => ':attribute deve essere maggiore di :value.',
        'string' => ':attribute deve avere più di :value caratteri.',
    ],

    'gte' => [
        'array' => ':attribute deve avere almeno :value elementi.',
        'file' => ':attribute deve pesare almeno :value kilobyte.',
        'numeric' => ':attribute deve essere almeno :value.',
        'string' => ':attribute deve avere almeno :value caratteri.',
    ],

    'hex_color' => ':attribute non è un colore esadecimale valido.',
    'image' => ':attribute deve essere un\'immagine.',
    'in' => ':attribute non è fra i valori ammessi.',
    'in_array' => ':attribute non compare fra :other.',
    'in_array_keys' => 'A :attribute manca almeno una di queste chiavi: :values.',
    'integer' => ':attribute deve essere un numero intero.',
    'ip' => ':attribute deve essere un indirizzo IP valido.',
    'ipv4' => ':attribute deve essere un indirizzo IPv4 valido.',
    'ipv6' => ':attribute deve essere un indirizzo IPv6 valido.',
    'json' => ':attribute deve essere una stringa JSON valida.',
    'list' => ':attribute deve essere un elenco.',
    'lowercase' => ':attribute deve essere tutto minuscolo.',

    'lt' => [
        'array' => ':attribute deve avere meno di :value elementi.',
        'file' => ':attribute deve pesare meno di :value kilobyte.',
        'numeric' => ':attribute deve essere minore di :value.',
        'string' => ':attribute deve avere meno di :value caratteri.',
    ],

    'lte' => [
        'array' => ':attribute non può avere più di :value elementi.',
        'file' => ':attribute non può pesare più di :value kilobyte.',
        'numeric' => ':attribute non può superare :value.',
        'string' => ':attribute non può avere più di :value caratteri.',
    ],

    'mac_address' => ':attribute deve essere un indirizzo MAC valido.',

    'max' => [
        'array' => ':attribute non può avere più di :max elementi.',
        'file' => ':attribute non può pesare più di :max kilobyte.',
        'numeric' => ':attribute non può superare :max.',
        'string' => ':attribute non può avere più di :max caratteri.',
    ],

    'max_digits' => ':attribute non può avere più di :max cifre.',
    'mimes' => ':attribute deve essere un file di tipo: :values.',
    'mimetypes' => ':attribute deve essere un file di tipo: :values.',

    'min' => [
        'array' => ':attribute deve avere almeno :min elementi.',
        'file' => ':attribute deve pesare almeno :min kilobyte.',
        'numeric' => ':attribute deve essere almeno :min.',
        'string' => ':attribute deve avere almeno :min caratteri.',
    ],

    'min_digits' => ':attribute deve avere almeno :min cifre.',
    'missing' => ':attribute non deve essere presente.',
    'missing_if' => ':attribute non deve essere presente quando :other è :value.',
    'missing_unless' => ':attribute non deve essere presente a meno che :other sia :value.',
    'missing_with' => ':attribute non deve essere presente insieme a :values.',
    'missing_with_all' => ':attribute non deve essere presente insieme a :values.',
    'multiple_of' => ':attribute deve essere un multiplo di :value.',
    'not_in' => ':attribute non è fra i valori ammessi.',
    'not_regex' => ':attribute non ha un formato valido.',
    'numeric' => ':attribute deve essere un numero.',

    /*
     * `uncompromised` è quella che si incontra davvero: la password compare
     * in un elenco di credenziali trapelate, e il controllo scatta solo in
     * produzione. La frase deve dire cosa fare, non solo che è andata male.
     */
    'password' => [
        'letters' => ':attribute deve contenere almeno una lettera.',
        'mixed' => ':attribute deve contenere almeno una maiuscola e una minuscola.',
        'numbers' => ':attribute deve contenere almeno un numero.',
        'symbols' => ':attribute deve contenere almeno un simbolo.',
        'uncompromised' => 'Questa password è comparsa in una fuga di dati: scegline un\'altra.',
    ],

    'present' => 'Serve :attribute.',
    'present_if' => ':attribute deve essere presente quando :other è :value.',
    'present_unless' => ':attribute deve essere presente a meno che :other sia :value.',
    'present_with' => ':attribute deve essere presente insieme a :values.',
    'present_with_all' => ':attribute deve essere presente insieme a :values.',
    'prohibited' => 'Non puoi usare :attribute.',
    'prohibited_if' => 'Non puoi usare :attribute quando :other è :value.',
    'prohibited_if_accepted' => 'Non puoi usare :attribute quando :other è accettato.',
    'prohibited_if_declined' => 'Non puoi usare :attribute quando :other è rifiutato.',
    'prohibited_unless' => 'Non puoi usare :attribute a meno che :other sia fra :values.',
    'prohibits' => ':attribute impedisce a :other di essere presente.',
    'regex' => ':attribute non ha un formato valido.',
    'required' => 'Serve :attribute.',
    'required_array_keys' => 'A :attribute mancano le voci: :values.',
    'required_if' => 'Serve :attribute quando :other è :value.',
    'required_if_accepted' => 'Serve :attribute quando :other è accettato.',
    'required_if_declined' => 'Serve :attribute quando :other è rifiutato.',
    'required_unless' => 'Serve :attribute a meno che :other sia fra :values.',
    'required_with' => 'Serve :attribute insieme a :values.',
    'required_with_all' => 'Serve :attribute insieme a :values.',
    'required_without' => 'Serve :attribute quando manca :values.',
    'required_without_all' => 'Serve :attribute quando mancano tutti: :values.',
    'same' => ':attribute e :other devono coincidere.',

    'size' => [
        'array' => ':attribute deve avere :size elementi.',
        'file' => ':attribute deve pesare :size kilobyte.',
        'numeric' => ':attribute deve essere :size.',
        'string' => ':attribute deve avere :size caratteri.',
    ],

    'starts_with' => ':attribute deve iniziare con: :values.',
    'string' => ':attribute deve essere testo.',
    'timezone' => ':attribute deve essere un fuso orario valido.',
    'unique' => ':attribute è già in uso.',
    'uploaded' => 'Il caricamento di :attribute non è riuscito.',
    'uppercase' => ':attribute deve essere tutto maiuscolo.',
    'url' => ':attribute non è un indirizzo valido.',
    'ulid' => ':attribute non è un ULID valido.',
    'uuid' => ':attribute non è un UUID valido.',

    /*
     * Messaggi per un campo e una regola precisi. Nel codice se ne passano
     * già molti a mano al momento della validazione, e quelli vincono su
     * questi: qui vanno solo quelli che vale la pena avere ovunque.
     */
    'custom' => [
        'password' => [
            'confirmed' => 'Le due password non coincidono.',
        ],
    ],

    /*
     * Come si chiamano i campi nelle frasi qui sopra. Senza, Laravel usa il
     * nome della colonna: «email è obbligatorio» invece di «l'email è
     * obbligatoria».
     */
    'attributes' => [
        'name' => 'il nome',
        'email' => 'l\'email',
        'password' => 'la password',
        'password_confirmation' => 'la conferma della password',
        'current_password' => 'la password attuale',
        'title' => 'il titolo',
        'description' => 'la descrizione',
        'message' => 'il messaggio',
        'note' => 'la nota',
        'reason' => 'il motivo',
        'story' => 'la storia',
        'notes' => 'le note',
        'price' => 'il prezzo',
        'qty' => 'la quantità',
    ],
];
