@php
    use App\Domain\Dnd\Ability;
    use App\Enums\Icon;
@endphp

<div>
    <p class="mb-1 mt-4 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-muted">
        <x-icona :is="Icon::Attacks" class="h-3.5 w-3.5" /> Attacchi
    </p>

    <div>
    @foreach ($inMano->isNotEmpty() ? $inMano : [$senzArmi] as $attacco)
        <div class="flex items-baseline justify-between gap-3 border-t border-line py-2 first:border-t-0">
            <span class="font-medium text-fg">{{ $attacco['name'] }}</span>
            <span class="whitespace-nowrap text-sm text-muted">
                <span class="font-bold text-fg">{{ Ability::format($attacco['attack']) }} colpire</span> · {{ $attacco['damage'] }}{{ filled($attacco['damage_type'] ?? null) ? ' '.$attacco['damage_type'] : '' }}
            </span>
        </div>
    @endforeach
    </div>

    @if ($inMano->isEmpty())
        <p class="text-xs text-muted">Nessuna arma in mano: impugnala dallo zaino.</p>
    @endif
</div>
