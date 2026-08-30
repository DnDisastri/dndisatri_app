<?php

declare(strict_types=1);

use App\Actions\Supervision\RejectSupervisedAction;
use App\Actions\Supervision\Supervisor;
use App\Actions\Users\IssueWarning;
use App\Enums\PendingChangeStatus;
use App\Filament\Resources\SupervisedActions\Pages\ViewSupervisedAction;
use App\Filament\Resources\SupervisedActions\SupervisedActionResource;
use App\Models\Character;
use App\Models\SupervisedAction;
use App\Models\User;
use Livewire\Livewire;

// La Resource permette ai DM di esaminare e decidere le azioni trattenute dalla supervisione.
beforeEach(function () {
    $this->dm = User::factory()->dm()->create();

    $this->sorvegliato = User::factory()->player()->create();
    $this->anna = Character::factory()->ownedBy($this->sorvegliato)->create(['name' => 'Anna', 'gp' => 100]);
    $this->bruno = Character::factory()->create(['name' => 'Bruno', 'gp' => 100]);

    app(IssueWarning::class)->handle($this->sorvegliato, $this->dm, 'Ha venduto due volte lo stesso anello.');
});

function inAttesa(): SupervisedAction
{
    test()->anna->addToInventory('Spada Lunga', value: 15);

    return app(Supervisor::class)->createListing(
        test()->sorvegliato, test()->anna, 'Spada Lunga', 1, 40,
    );
}

describe('chi ci entra', function () {
    it('i DM e gli admin', function () {
        inAttesa();

        foreach ([$this->dm, User::factory()->admin()->create()] as $chi) {
            $this->actingAs($chi)
                ->get(SupervisedActionResource::getUrl('index'))
                ->assertOk();
        }
    });

    it('un giocatore no: le sue le segue dal mercato', function () {
        $this->actingAs($this->sorvegliato);

        expect(SupervisedActionResource::canViewAny())->toBeFalse();
    });
});

describe('la bacheca', function () {
    it('mostra chi ha chiesto cosa, e da quanto aspetta', function () {
        inAttesa();

        $this->actingAs($this->dm)
            ->get(SupervisedActionResource::getUrl('index'))
            ->assertOk()
            ->assertSee($this->sorvegliato->name)
            ->assertSee('Vuole mettere in vendita 1× Spada Lunga per 40 mo');
    });

    it('e conta sul menu quelle che aspettano', function () {
        expect(SupervisedActionResource::getNavigationBadge())->toBeNull();

        inAttesa();

        expect(SupervisedActionResource::getNavigationBadge())->toBe('1');
    });
});

// Il riepilogo serve a scegliere cosa aprire; i dettagli completi servono prima della decisione.
describe('il dettaglio', function () {
    it('scioglie il payload in righe leggibili', function () {
        $azione = inAttesa();

        expect(collect($azione->details())->pluck('valore', 'voce')->all())
            ->toBe(['Chi vende' => 'Anna', 'Cosa' => '1× Spada Lunga', 'Prezzo' => '40 mo']);
    });

    it('e in uno scambio dice cosa offre e cosa chiede', function () {
        $this->anna->addToInventory('Corda di Seta');

        $azione = app(Supervisor::class)->proposeTrade(
            $this->sorvegliato, $this->anna, $this->bruno,
            give: [['name' => 'Corda di Seta', 'qty' => 1]],
            wantGp: 30,
        );

        $righe = collect($azione->details())->pluck('valore', 'voce');

        expect($righe['Da'])->toBe('Anna')
            ->and($righe['A'])->toBe('Bruno')
            ->and($righe['Offre'])->toBe('1× Corda di Seta')
            ->and($righe['Chiede'])->toBe('30 mo');
    });

    it('e le voci vuote non compaiono', function () {
        $azione = inAttesa();

        expect(collect($azione->details())->pluck('voce'))->not->toContain('Messaggio');
    });

    it('la pagina si apre e mostra il richiamo per cui è sotto controllo', function () {
        $azione = inAttesa();

        $this->actingAs($this->dm)
            ->get(SupervisedActionResource::getUrl('view', ['record' => $azione]))
            ->assertOk()
            ->assertSee('Anna')
            ->assertSee('Ha venduto due volte lo stesso anello.');
    });
});

describe('il via libera', function () {
    it('esegue davvero l\'operazione', function () {
        $azione = inAttesa();

        Livewire::actingAs($this->dm)
            ->test(ViewSupervisedAction::class, ['record' => $azione->getKey()])
            ->callAction('approva')
            ->assertHasNoActionErrors();

        expect($azione->fresh()->status)->toBe(PendingChangeStatus::Approved)
            ->and(App\Models\MarketListing::where('name', 'Spada Lunga')->exists())->toBeTrue();
    });
// L'approvazione riesegue l'azione sullo stato corrente e può quindi fallire senza chiudere la richiesta.
    it('ma se nel frattempo l\'oggetto non c\'è più, lo dice e non decide', function () {
        $azione = inAttesa();
        $this->anna->removeFromInventory('Spada Lunga');

        Livewire::actingAs($this->dm)
            ->test(ViewSupervisedAction::class, ['record' => $azione->getKey()])
            ->callAction('approva');

        expect($azione->fresh()->isPending())->toBeTrue();
    });
});

describe('il blocco', function () {
    it('senza motivo non si dà', function () {
        $azione = inAttesa();

        Livewire::actingAs($this->dm)
            ->test(ViewSupervisedAction::class, ['record' => $azione->getKey()])
            ->callAction('rifiuta', ['note' => ''])
            ->assertHasActionErrors(['note']);

        expect($azione->fresh()->isPending())->toBeTrue();
    });

    it('col motivo blocca, e il motivo resta scritto', function () {
        $azione = inAttesa();

        Livewire::actingAs($this->dm)
            ->test(ViewSupervisedAction::class, ['record' => $azione->getKey()])
            ->callAction('rifiuta', ['note' => 'Quaranta monete per una spada da quindici.'])
            ->assertHasNoActionErrors();

        $deciso = $azione->fresh();

        expect($deciso->status)->toBe(PendingChangeStatus::Rejected)
            ->and($deciso->review_note)->toBe('Quaranta monete per una spada da quindici.')
            // L'azione non era mai stata eseguita, quindi non c'è nulla da annullare.
            ->and(App\Models\MarketListing::count())->toBe(0);
    });
});
// Il revisore non può essere coinvolto nell'azione tramite uno dei propri personaggi.
describe('chi non può decidere', function () {
    it('un DM che ha un personaggio dentro l\'operazione', function () {
        $dmInMezzo = User::factory()->dm()->create();
        $suo = Character::factory()->ownedBy($dmInMezzo)->create(['name' => 'Carla', 'gp' => 100]);

        $this->anna->addToInventory('Corda di Seta');

        $azione = app(Supervisor::class)->proposeTrade(
            $this->sorvegliato, $this->anna, $suo,
            give: [['name' => 'Corda di Seta', 'qty' => 1]],
        );

        expect($dmInMezzo->can('approve', $azione))->toBeFalse()
            ->and($this->dm->can('approve', $azione))->toBeTrue();

        Livewire::actingAs($dmInMezzo)
            ->test(ViewSupervisedAction::class, ['record' => $azione->getKey()])
            ->assertActionHidden('approva')
            ->assertActionHidden('rifiuta');
    });

    it('e su una già decisa i pulsanti non ci sono più', function () {
        $azione = inAttesa();

        app(RejectSupervisedAction::class)->handle($azione, $this->dm, 'Prezzo fuori mercato.');

        Livewire::actingAs($this->dm)
            ->test(ViewSupervisedAction::class, ['record' => $azione->fresh()->getKey()])
            ->assertActionHidden('approva')
            ->assertActionHidden('rifiuta');
    });
});
