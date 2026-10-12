<?php

declare(strict_types=1);

use App\Models\Event;
use App\Support\ImageShrinker;
use Illuminate\Support\Facades\Storage;

/** Un JPEG di rumore: pesante e grande come una foto del telefono. */
function fotoFinta(string $percorso, int $larghezza, int $altezza): void
{
    $img = imagecreatetruecolor($larghezza, $altezza);

    for ($i = 0; $i < 4000; $i++) {
        imagefilledrectangle($img, random_int(0, $larghezza), random_int(0, $altezza), random_int(0, $larghezza), random_int(0, $altezza),
            imagecolorallocate($img, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
    }

    ob_start();
    imagejpeg($img, null, 95);
    Storage::disk('public')->put($percorso, ob_get_clean());
}

beforeEach(fn () => Storage::fake('public'));

it('riduce il lato lungo e alleggerisce il file', function () {
    fotoFinta('eventi/grande.jpg', 3200, 2000);
    $prima = Storage::disk('public')->size('eventi/grande.jpg');

    $risparmio = app(ImageShrinker::class)->shrink('public', 'eventi/grande.jpg', 1600);

    [$larghezza, $altezza] = getimagesize(Storage::disk('public')->path('eventi/grande.jpg'));
    expect($larghezza)->toBe(1600)
        ->and($altezza)->toBe(1000)
        ->and($risparmio)->toBeGreaterThan(0)
        ->and(Storage::disk('public')->size('eventi/grande.jpg'))->toBeLessThan($prima);
});

it('lascia stare un\'immagine già piccola e leggera', function () {
    fotoFinta('eventi/piccola.jpg', 400, 300);
    $prima = Storage::disk('public')->get('eventi/piccola.jpg');

    expect(app(ImageShrinker::class)->shrink('public', 'eventi/piccola.jpg', 1600))->toBeNull()
        ->and(Storage::disk('public')->get('eventi/piccola.jpg'))->toBe($prima);
});

it('in prova non tocca il file', function () {
    fotoFinta('eventi/grande.jpg', 3200, 2000);
    $prima = Storage::disk('public')->get('eventi/grande.jpg');

    expect(app(ImageShrinker::class)->shrink('public', 'eventi/grande.jpg', 1600, dryRun: true))->toBeGreaterThan(0)
        ->and(Storage::disk('public')->get('eventi/grande.jpg'))->toBe($prima);
});

it('riduce la copertina appena salvata', function () {
    fotoFinta('eventi/copertina.jpg', 3200, 2000);

    Event::factory()->create(['cover_path' => 'eventi/copertina.jpg']);

    expect(getimagesize(Storage::disk('public')->path('eventi/copertina.jpg'))[0])->toBe(ImageShrinker::COVER);
});

it('il comando, con --dry-run, dice quanto risparmierebbe senza toccare niente', function () {
    fotoFinta('eventi/copertina.jpg', 3200, 2000);
    $evento = Event::factory()->create(['cover_path' => null]);
    $evento->forceFill(['cover_path' => 'eventi/copertina.jpg'])->saveQuietly();
    $prima = Storage::disk('public')->get('eventi/copertina.jpg');

    $this->artisan('dndisastri:riduci-immagini', ['--dry-run' => true])
        ->expectsOutputToContain('Prova: niente è stato toccato')
        ->assertSuccessful();

    expect(Storage::disk('public')->get('eventi/copertina.jpg'))->toBe($prima);
});
