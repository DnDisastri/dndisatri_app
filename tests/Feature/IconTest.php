<?php

declare(strict_types=1);

use App\Enums\Icon;
use BladeUI\Icons\Factory;
use Illuminate\Support\Facades\Blade;


it('disegna davvero ognuna delle icone dichiarate', function () {
    $factory = app(Factory::class);

    foreach (Icon::cases() as $icona) {
        expect(fn () => $factory->svg($icona->blade()))
            ->not->toThrow(Throwable::class, "L'icona {$icona->name} non esiste");
    }
});

// La misura richiesta deve sostituire quella predefinita: classi Tailwind concorrenti non garantiscono che vinca l'ultima scritta nell'HTML.
it('lascia scegliere la misura senza sommarla a quella predefinita', function () {
    $html = Blade::render(
        '<x-icona :is="$icona" class="h-3 w-3 text-primary" title="Competente" />',
        ['icona' => Icon::Proficient],
    );

    expect(preg_match_all('/\bh-\d+\b/', $html))->toBe(1)
        ->and($html)->toContain('h-3 w-3')
        ->and($html)->toContain('text-primary')
        ->and($html)->toContain('title="Competente"');
});

it('usa la misura predefinita quando non gliene si dà una', function () {
    $html = Blade::render('<x-icona :is="$icona" />', ['icona' => Icon::Notifications]);

    expect($html)->toContain('h-6 w-6');
});

it('dà a ogni icona un nome del pacchetto', function () {
    foreach (Icon::cases() as $icona) {
        expect($icona->blade())->toStartWith('phosphor-');
    }
});
// I nomi delle icone devono restare centralizzati negli enum che implementano `Icona`.
it('non lascia nomi di icone sparsi nel codice', function () {
    $radici = [base_path('app'), resource_path('views')];

// L'elenco consentito viene ricavato dal contratto, così nuovi enum di icone vengono riconosciuti automaticamente.
    $consentiti = collect(glob(base_path('app/Enums/*.php')))
        ->filter(fn (string $f) => str_contains((string) file_get_contents($f), 'implements Icona'))
        ->map(fn (string $f) => str_replace('\\', '/', $f))
        ->push(str_replace('\\', '/', resource_path('views/components/icona.blade.php')))
        ->values()
        ->all();

    expect($consentiti)->toContain(str_replace('\\', '/', base_path('app/Enums/Icon.php')));

    $colpevoli = [];

    foreach ($radici as $radice) {
        $file = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($radice));

        foreach ($file as $f) {
            if (! $f->isFile() || ! in_array($f->getExtension(), ['php'], true)) {
                continue;
            }

            $percorso = str_replace('\\', '/', $f->getPathname());

            if (in_array($percorso, $consentiti, true)) {
                continue;
            }

            if (preg_match('/heroicon-|x-phosphor-|Phosphor::/', (string) file_get_contents($percorso))) {
                $colpevoli[] = str_replace(str_replace('\\', '/', base_path()).'/', '', $percorso);
            }
        }
    }

    expect($colpevoli)->toBe([]);
});
