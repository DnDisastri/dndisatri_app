<?php

declare(strict_types=1);

use App\Filament\Resources\AboutPages\AboutPageResource;
use App\Filament\Resources\AboutPages\Pages\EditAboutPage;
use App\Models\AboutPage;
use App\Models\User;
use Livewire\Livewire;

it('è pubblica e mostra testo, copertina e social validi', function () {
    AboutPage::create([
        'body' => 'Siamo la gilda dei disastri.',
        'cover_path' => 'chi-siamo/copertina.jpg',
        'socials' => [
            'instagram' => 'https://instagram.com/gilda',
            'tiktok' => 'javascript:alert(1)',
            'telegram' => null,
        ],
    ]);

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Siamo la gilda dei disastri.')
        ->assertSee('chi-siamo/copertina.jpg', false)
        ->assertSee('https://instagram.com/gilda')
        ->assertDontSee('javascript:alert(1)', false);
});

it('si raggiunge dalla presentazione, senza accesso', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('about'));
});

it('per chi ha fatto accesso è nel menù in alto', function () {
    $this->actingAs(User::factory()->player()->create())
        ->get('/')
        ->assertOk()
        ->assertSee(route('about'));
});

it('la modifica solo un admin', function () {
    $page = AboutPage::create(['body' => 'x', 'socials' => []]);

    expect(User::factory()->admin()->create()->can('update', $page))->toBeTrue()
        ->and(User::factory()->dm()->create()->can('update', $page))->toBeFalse()
        ->and(User::factory()->player()->create()->can('update', $page))->toBeFalse();
});

it('salva i link social dal pannello', function () {
    $page = AboutPage::create(['body' => 'x', 'socials' => []]);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(EditAboutPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['socials' => ['instagram' => 'https://instagram.com/gilda']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh()->socials['instagram'])->toBe('https://instagram.com/gilda');
});

it('nel pannello ci lavora solo un admin', function () {
    $page = AboutPage::create(['body' => 'x', 'socials' => []]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(AboutPageResource::getUrl('edit', ['record' => $page]))
        ->assertOk();

    $this->actingAs(User::factory()->dm()->create())
        ->get(AboutPageResource::getUrl('index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->player()->create())
        ->get(AboutPageResource::getUrl('index'))
        ->assertForbidden();
});
