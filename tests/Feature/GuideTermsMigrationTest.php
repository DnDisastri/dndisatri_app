<?php

declare(strict_types=1);

use App\Models\Faq;
use App\Models\TutorialStep;

it('porta tutorial e FAQ ancora originali ai nomi nuovi, e lascia stare quelli modificati', function () {
    $passo = TutorialStep::factory()->create([
        'title' => 'Trova un incarico',
        'body' => 'Gli **incarichi** sono le storie aperte a cui puoi partecipare. Apri quello '
            .'che ti interessa e usa **Prenotati** con uno dei tuoi eroi, se ci sono posti '
            ."liberi.\n\n"
            .'Puoi **ritirarti** finché la serata non è fissata. Quando chi conduce sceglie la '
            .'data, ricevi una notifica.',
    ]);
    $passoModificato = TutorialStep::factory()->create(['title' => 'Il mercato', 'body' => 'Scritto da un admin.']);

    $faq = Faq::create([
        'category' => 'Incarichi',
        'question' => "Cos'è un incarico?",
        'answer' => 'È una storia aperta a cui puoi partecipare, con un numero di posti. Nella pagina Incarichi vedi quelli disponibili, la difficoltà e quanti posti restano liberi.',
        'position' => 5,
    ]);
    $faqModificata = Faq::create([
        'category' => 'Incarichi',
        'question' => 'Come mi prenoto?',
        'answer' => 'Scritta da un admin.',
        'position' => 6,
    ]);

    (require database_path('migrations/2026_10_09_110000_refresh_guide_terms.php'))->up();

    $seed = collect(require database_path('seeders/data/tutorial.php'))->firstWhere('title', 'Trova una quest');

    expect($passo->fresh())->title->toBe('Trova una quest')->body->toBe($seed['body'])
        ->and($passoModificato->fresh()->body)->toBe('Scritto da un admin.')
        ->and($faq->fresh())->category->toBe('Quest')->question->toBe("Cos'è una quest?")
        ->and($faqModificata->fresh())->category->toBe('Incarichi')->answer->toBe('Scritta da un admin.');
});
