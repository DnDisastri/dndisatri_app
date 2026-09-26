@props(['testo'])

@php
    use App\Enums\Icon;
    use BladeUI\Icons\Factory;

    /*
     * `**parola**` diventa grassetto, `[icona:nome]` un'icona (nome = valore di
     * un caso di `Icon`). Il resto è messo in escape: l'HTML è sicuro anche se
     * il testo arriva dal pannello. Ogni riga è un blocco; una riga vuota nel
     * testo diventa uno stacco maggiore.
     */
    $factory = app(Factory::class);

    $inline = function (string $riga) use ($factory): string {
        $parti = preg_split(
            '/(\*\*[^*]+\*\*|\[icona:[a-z-]+\])/u',
            $riga,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY,
        ) ?: [];

        $html = '';

        foreach ($parti as $pezzo) {
            if (str_starts_with($pezzo, '**') && str_ends_with($pezzo, '**')) {
                $html .= '<strong>'.e(trim($pezzo, '*')).'</strong>';
            } elseif (str_starts_with($pezzo, '[icona:')) {
                $icona = Icon::tryFrom(substr($pezzo, 7, -1));

                if ($icona) {
                    $html .= $factory->svg($icona->blade(), 'mr-2 inline-block h-6 w-6 -translate-y-px align-middle')->toHtml();
                }
            } else {
                $html .= e($pezzo);
            }
        }

        return $html;
    };

    $html = '';
    $primaRiga = true;
    $vuotaPrima = false;

    foreach (preg_split('/\r?\n/', trim((string) $testo)) as $riga) {
        if (trim($riga) === '') {
            $vuotaPrima = true;
            continue;
        }

        $stacco = $primaRiga ? '' : ($vuotaPrima ? 'mt-3' : 'mt-1.5');
        $html .= '<p class="'.$stacco.'">'.$inline($riga).'</p>';

        $primaRiga = false;
        $vuotaPrima = false;
    }
@endphp

<div {{ $attributes->merge(['class' => 'leading-relaxed']) }}>{!! $html !!}</div>
