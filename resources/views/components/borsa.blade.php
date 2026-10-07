@props(['borsa', 'compatta' => false])

{{-- Le quattro monete in caselle; `compatta` per le intestazioni, come nel mercato. --}}
<dl {{ $attributes->class(['grid grid-cols-4', 'gap-1' => $compatta, 'gap-2' => ! $compatta]) }}>
    @foreach (\App\Domain\Dnd\Coin::descending() as $moneta)
        {{-- La compatta ha il giallo del vecchio badge del mercato. --}}
        <div @class([
            'rounded-md text-center',
            'min-w-10 bg-accent-soft px-1.5 py-0.5 text-on-accent-soft' => $compatta,
            'border border-line bg-page px-2 py-1' => ! $compatta,
        ])>
            <dt @class(['text-[10px] leading-tight opacity-75' => $compatta, 'text-xs text-muted' => ! $compatta])
                title="{{ $moneta->label() }}">{{ $moneta->abbreviation() }}</dt>
            <dd @class(['font-bold', 'text-sm leading-tight' => $compatta, 'text-fg' => ! $compatta])>
                {{ number_format($borsa->get($moneta), 0, ',', '.') }}
            </dd>
        </div>
    @endforeach
</dl>
