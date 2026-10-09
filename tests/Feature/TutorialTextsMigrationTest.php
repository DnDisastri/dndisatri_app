<?php

declare(strict_types=1);

use App\Models\TutorialStep;

it('aggiorna i passi ancora originali e lascia stare quelli modificati', function () {
    $originale = "[icona:shop] **Emporio**: compri dal negozio della gilda.\n"
        ."[icona:listings] **Annunci**: metti in vendita i tuoi oggetti.\n"
        .'[icona:trades] **Scambi**: proponi uno scambio a un altro giocatore, che accetta '
        ."o rifiuta.\n\n"
        .'Tutto passa per l\'**oro** dei tuoi personaggi.';

    $mercato = TutorialStep::factory()->create(['title' => 'Il mercato', 'body' => $originale]);
    $modificato = TutorialStep::factory()->create(['title' => 'Trova un incarico', 'body' => 'Scritto da un admin.']);

    (require database_path('migrations/2026_10_07_130000_refresh_tutorial_texts.php'))->up();

    $nuovo = collect(require database_path('seeders/data/tutorial.php'))->firstWhere('title', 'Il mercato')['body'];

    expect($mercato->fresh()->body)->toBe($nuovo)
        ->and($modificato->fresh()->body)->toBe('Scritto da un admin.');
});
