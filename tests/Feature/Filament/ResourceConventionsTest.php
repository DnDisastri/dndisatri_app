<?php

declare(strict_types=1);

/**
 * Il canone dei pannelli Filament (reso regola).
 *
 * Le risorse seguono uno stile comune; questo test lo blinda. La regola
 * verificata qui:
 *
 *   Ogni colonna di tabella, campo di form e voce di infolist ha una `->label()`
 *   esplicita in italiano, mai affidarsi all'umanizzazione automatica di
 *   Filament (che produce "Created by", "Starts at", ecc.).
 *
 * Le altre convenzioni del canone (non verificate automaticamente ma attese):
 *  - i form sono avvolti in `Section` con titolo;
 *  - le chiavi esterne non sono campi numerici: relazione o assegnazione
 *    automatica lato pagina (`mutateFormDataBeforeCreate`);
 *  - le tabelle hanno `defaultSort`, `emptyStateHeading` e niente filtri `//`;
 *  - le colonne secondarie si nascondono su mobile con `->visibleFrom('md')`.
 *
 * Chi aggiunge una colonna o un campo senza label fa fallire questo test: è
 * intenzionale. Una label vuota voluta (`->label('')`) è ammessa e passa.
 */

// Componenti che DEVONO avere una label, per tipo di file.
$vincoli = [
    '*/Tables/*Table.php' => ['TextColumn', 'IconColumn', 'ImageColumn', 'ColorColumn'],
    '*/Schemas/*Form.php' => [
        'TextInput', 'Textarea', 'Select', 'Toggle', 'Checkbox', 'CheckboxList',
        'Radio', 'FileUpload', 'Repeater', 'DatePicker', 'DateTimePicker',
        'TimePicker', 'TagsInput', 'RichEditor', 'MarkdownEditor',
    ],
    '*/Schemas/*Infolist.php' => ['TextEntry', 'IconEntry', 'ImageEntry', 'ColorEntry'],
];

/**
 * Le componenti prive di `->label()` in un file, con il nome del campo.
 *
 * @param  list<string>  $classi
 * @return list<string>
 */
function componentiSenzaLabel(string $contenuto, array $classi): array
{
    // I confini fra una componente e la successiva: ogni `::make(` apre un
    // nuovo blocco, così la label di ciascuna si cerca solo nel suo tratto di
    // catena (le label si scrivono prima di `->schema()`, quindi dentro il
    // proprio blocco anche per i Repeater annidati).
    preg_match_all('/::make\(/', $contenuto, $_, PREG_OFFSET_CAPTURE);
    $confini = array_map(fn ($m) => $m[1], $_[0]);

    $elenco = implode('|', $classi);
    preg_match_all(
        "/\\b({$elenco})::make\\(\\s*'([^']*)'/",
        $contenuto,
        $target,
        PREG_OFFSET_CAPTURE
    );

    $mancanti = [];

    foreach ($target[0] as $i => $match) {
        $inizio = $match[1];
        // Fine del `::make('nome'` della componente stessa: la finestra va da
        // qui al `::make(` successivo, così la label si cerca solo nella catena
        // di questa componente.
        $dopoMake = $inizio + strlen($match[0]);
        $fine = collect($confini)->first(fn ($c) => $c > $dopoMake) ?? strlen($contenuto);
        $blocco = substr($contenuto, $inizio, $fine - $inizio);

        // `hiddenLabel()` è una scelta deliberata (l'etichetta la dà la sezione),
        // non una dimenticanza: vale come label esplicita.
        if (! str_contains($blocco, '->label(') && ! str_contains($blocco, '->hiddenLabel(')) {
            $mancanti[] = "{$target[1][$i][0]}::make('{$target[2][$i][0]}')";
        }
    }

    return $mancanti;
}

// dirname(__DIR__, 3) = radice del progetto (tests/Feature/Filament → root).
// Si evita app_path(): la raccolta gira prima del boot completo dell'app.
$risorse = dirname(__DIR__, 3).'/app/Filament/Resources';

$casi = [];
foreach ($vincoli as $glob => $classi) {
    foreach (glob("{$risorse}/{$glob}") as $file) {
        $chiave = str_replace('\\', '/', $file);
        $chiave = substr($chiave, strpos($chiave, 'Resources/') + strlen('Resources/'));
        $casi[$chiave] = [$file, $classi];
    }
}

it('ogni colonna, campo e voce ha una label italiana', function (string $file, array $classi) {
    $mancanti = componentiSenzaLabel((string) file_get_contents($file), $classi);

    expect($mancanti)->toBe([], 'Senza ->label(): '.implode(', ', $mancanti));
})->with($casi);
