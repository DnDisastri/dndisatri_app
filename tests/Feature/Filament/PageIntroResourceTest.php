<?php

declare(strict_types=1);

use App\Enums\IntroPage;
use App\Filament\Resources\PageIntros\PageIntroResource;
use App\Filament\Resources\PageIntros\Pages\EditPageIntro;
use App\Models\PageIntro;
use App\Models\User;
use Livewire\Livewire;

describe('le introduzioni delle pagine', function () {
    it('senza modifiche mostrano il testo di partenza', function () {
        $this->actingAs(User::factory()->player()->create())
            ->get(route('campaigns.index'))
            ->assertOk()
            ->assertSee(IntroPage::Campaigns->default());
    });

    it('mostrano il testo salvato dal pannello, con gli a capo', function () {
        PageIntro::create(['page' => IntroPage::Campaigns, 'body' => "Prima riga\nseconda <b>riga</b>"]);

        $this->actingAs(User::factory()->player()->create())
            ->get(route('campaigns.index'))
            ->assertOk()
            ->assertSee('Prima riga<br />', false)
            ->assertSee('seconda &lt;b&gt;riga&lt;/b&gt;', false)
            ->assertDontSee(IntroPage::Campaigns->default());
    });
});

describe('la sezione delle introduzioni nel pannello', function () {
    it('elenca tutte le pagine anche se mancano le righe', function () {
        $this->actingAs(User::factory()->admin()->create())
            ->get(PageIntroResource::getUrl('index'))
            ->assertOk()
            ->assertSee(IntroPage::Encounters->label());

        expect(PageIntro::count())->toBe(count(IntroPage::cases()));
    });

    it('salva il testo nuovo', function () {
        PageIntro::fillMissing();
        $intro = PageIntro::where('page', IntroPage::Shop)->first();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(EditPageIntro::class, ['record' => $intro->getRouteKey()])
            ->fillForm(['body' => 'Il negozio della gilda.'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($intro->fresh()->body)->toBe('Il negozio della gilda.');
    });

    it('resta chiusa ai DM e ai giocatori', function () {
        $this->actingAs(User::factory()->dm()->create())
            ->get(PageIntroResource::getUrl('index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->player()->create())
            ->get(PageIntroResource::getUrl('index'))
            ->assertForbidden();
    });
});
