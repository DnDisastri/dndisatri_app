<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Blade;


function resa(string $blade): string
{
    return (string) Blade::render($blade);
}

it('è un bottone se non gli si dà un indirizzo', function () {
    $html = resa('<x-button>Prenota</x-button>');

    expect($html)->toContain('<button')
        ->and($html)->toContain('Prenota')
        ->and($html)->not->toContain('<a ');
});

it('è un collegamento se gli si dà un indirizzo', function () {
    $html = resa('<x-button href="/incarichi">Vai</x-button>');

    expect($html)->toContain('<a href="/incarichi"')
        ->and($html)->not->toContain('<button');
});

it('parte da submit, perché quasi sempre sta dentro un modulo', function () {
    expect(resa('<x-button>Manda</x-button>'))->toContain('type="submit"');
});

it('lascia dichiarare un tipo diverso', function () {
    $html = resa('<x-button type="button">Non manda niente</x-button>');

    expect($html)->toContain('type="button"')
        ->and($html)->not->toContain('type="submit"');
});
// Le proprietà che scelgono una variante sostituiscono le classi predefinite dello stesso asse invece di accodarle.
it('sostituisce la misura invece di accodarla', function () {
    $piccolo = resa('<x-button size="sm">Piccolo</x-button>');

    expect($piccolo)->toContain('px-3 py-1.5')
        ->and($piccolo)->not->toContain('px-6 py-3')
        ->and($piccolo)->not->toContain('px-4 py-2 ');
});

it('sostituisce il tono invece di accodarlo', function () {
    $secondario = resa('<x-button variant="quiet">Annulla</x-button>');

    expect($secondario)->toContain('border-line')
        ->and($secondario)->not->toContain('bg-active');
});
// Le classi di layout passate dal chiamante devono invece aggiungersi a quelle proprie del componente.
it('accoda le classi che aggiungono, senza perdere le sue', function () {
    $html = resa('<x-button class="flex-1">Salva</x-button>');

    expect($html)->toContain('flex-1')
        ->and($html)->toContain('bg-active');
});

it('a tutta larghezza solo quando glielo si chiede', function () {
    expect(resa('<x-button full>Manda</x-button>'))->toContain('w-full')
        ->and(resa('<x-button>Manda</x-button>'))->not->toContain('w-full');
});

it('dà tre toni alla pillola, e uno solo per volta', function () {
    $allarme = resa('<x-badge tone="danger">Caduto</x-badge>');

    expect($allarme)->toContain('bg-danger-soft')
        ->and($allarme)->not->toContain('bg-off')
        ->and($allarme)->not->toContain('bg-accent-soft');
});
// Il tono neutro usa `quiet` perché `off` non garantisce contrasto sufficiente per testo leggibile.
it('la pillola senza tono è quella neutra, e usa un colore leggibile', function () {
    $html = resa('<x-badge>Conclusa</x-badge>');

    expect($html)->toContain('bg-quiet')
        ->and($html)->not->toContain('bg-off');
});

it('mostra la vetrina fuori produzione', function () {
    $this->actingAs(User::factory()->player()->create())
        ->get(route('dev.components'))
        ->assertOk()
        ->assertSee('La vetrina')
        ->assertSee('Primario')
        ->assertSee('Secondario')
        ->assertSee('Difficile');
});
// Un attributo booleano falso non deve essere renderizzato: in HTML anche `disabled=""` disabilita il controllo.
it('spegne il pulsante solo quando glielo si chiede', function () {
    expect(resa('<x-button :disabled="true">Avanti</x-button>'))->toContain('disabled')
        ->and(resa('<x-button :disabled="false">Avanti</x-button>'))->not->toContain('disabled=');
});

it('la card ferma è un div, quella con un indirizzo è un collegamento', function () {
    expect(resa('<x-card>Contenuto</x-card>'))->toContain('<div')
        ->and(resa('<x-card href="/campagne">Contenuto</x-card>'))->toContain('<a href="/campagne"');
});

it('accende la card solo se porta da qualche parte', function () {
    expect(resa('<x-card href="/campagne">Vai</x-card>'))->toContain('hover:border-active')
        ->and(resa('<x-card>Fermo</x-card>'))->not->toContain('hover:');
});

it('sostituisce l\'imbottitura invece di accodarla', function () {
    $stretta = resa('<x-card padding="sm">Contenuto</x-card>');

    expect($stretta)->toContain('px-4 py-3')
        ->and($stretta)->not->toContain('p-4')
        ->and($stretta)->not->toContain('p-6');
});
// `padding="none"` permette al chiamante di gestire l'imbottitura senza lasciare classi concorrenti.
it('sa non mettere nessuna imbottitura', function () {
    $nuda = resa('<x-card padding="none">Contenuto</x-card>');

    expect($nuda)->not->toContain('p-4')
        ->and($nuda)->not->toContain('px-4')
        ->and($nuda)->toContain('border-line');
});

it('lo stato vuoto ha due misure, e una per volta', function () {
    expect(resa('<x-empty>Niente</x-empty>'))->toContain('py-6')
        ->and(resa('<x-empty>Niente</x-empty>'))->not->toContain('py-8')
        ->and(resa('<x-empty size="lg">Niente</x-empty>'))->toContain('py-8')
        ->and(resa('<x-empty size="lg">Niente</x-empty>'))->not->toContain('py-6');
});

it('il messaggio distingue quello che è andato bene da quello che no', function () {
    $male = resa('<x-note tone="danger">Non si può</x-note>');

    expect($male)->toContain('bg-danger-soft')
        ->and($male)->not->toContain('bg-accent-soft')
        ->and(resa('<x-note>Fatto</x-note>'))->toContain('bg-accent-soft');
});

it('il riquadro interno non ha bordo e prende il colore della pagina', function () {
    $dentro = resa('<x-inset>Contenuto</x-inset>');

    expect($dentro)->toContain('bg-page')
        ->and($dentro)->not->toContain('border');
});

it('il manifesto tiene l\'angolo quasi vivo in basso a destra', function () {
    $html = resa('<x-poster href="/eventi/1" title="Un evento" />');

    expect($html)->toContain('rounded-poster')
        ->and($html)->toContain('rounded-br-poster-cut')
        ->and($html)->not->toContain('rounded-card');
});
// Il velo mantiene leggibile il testo anche su immagini caricate dagli utenti con luminosità imprevedibile.
it('il manifesto vela sempre l\'immagine, anche quando non ce n\'è una', function () {
    $conFoto = resa('<x-poster href="#" title="Un evento" image="/x.jpg" />');
    $senza = resa('<x-poster href="#" title="Un evento" />');

    expect($conFoto)->toContain('bg-gradient-to-b')
        ->and($conFoto)->toContain('<img src="/x.jpg"')
        ->and($senza)->toContain('bg-primary')
        ->and($senza)->not->toContain('<img');
});

it('il manifesto non annida un collegamento dentro l\'altro', function () {
    $html = resa('<x-poster href="/eventi/1" title="Un evento" />');

    expect(substr_count($html, '<a '))->toBe(1);
});

it('il manifesto senza scritta mostra la sola freccia', function () {
    $nudo = resa('<x-poster href="#" title="Una campagna" action="" />');
    $conScritta = resa('<x-poster href="#" title="Una campagna" action="Entra" />');

    expect($nudo)->toContain('h-11 w-11')
        ->and($nudo)->toContain('self-end')
        ->and($nudo)->not->toContain('Entra')
        ->and($conScritta)->toContain('Entra')
        ->and($conScritta)->not->toContain('h-11 w-11');
});
