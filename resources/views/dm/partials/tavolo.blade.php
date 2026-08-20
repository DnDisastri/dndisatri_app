@props(['tavolo'])
{{-- La vista DM mostra PF, CA e oro, dati volutamente omessi dalle card pubbliche della Gilda. --}}
@if ($tavolo->isEmpty())
    <x-empty>Nessuno si è ancora seduto a questo tavolo.</x-empty>
@else
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @foreach ($tavolo as $pg)
            @php
                $max = $pg->effectiveHpMax();
                $cur = max(0, (int) $pg->hp_current);
                $frazione = $max > 0 ? min(1, $cur / $max) : 0;
                $aTerra = $cur <= 0;
                $barra = match (true) {
                    $aTerra, $frazione <= 0.33 => 'bg-active',
                    $frazione <= 0.66 => 'bg-accent',
                    default => 'bg-primary',
                };
            @endphp

            <a href="{{ route('characters.show', $pg) }}"
               @class([
                   'block rounded-card border border-line bg-surface p-3 transition',
                   'hover:border-active hover:-translate-y-0.5 hover:shadow-lg hover:shadow-black/10',
               ])>
                <div class="flex items-center gap-3">
                    @if ($pg->photoUrl())
                        <img src="{{ $pg->photoUrl() }}" alt="{{ $pg->name }}"
                             class="h-11 w-11 shrink-0 rounded-lg object-cover">
                    @else
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-page">
                            <x-icona :is="\App\Enums\Icon::Characters" class="h-5 w-5 text-muted" />
                        </span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-display text-base text-fg">{{ $pg->name }}</p>
                        <p class="truncate text-xs text-muted">{{ $pg->class }} · liv. {{ $pg->level }}</p>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="mb-1 flex items-baseline justify-between text-xs">
                        @if ($aTerra)
                            <span class="font-semibold text-on-danger-soft">A terra</span>
                            <span class="text-on-danger-soft">0 PF</span>
                        @else
                            <span class="text-muted">PF</span>
                            <span class="font-semibold text-fg">{{ $cur }} / {{ $max }}</span>
                        @endif
                    </div>

                    <div class="h-2 overflow-hidden rounded-full border border-line bg-page">
                        <span class="block h-full rounded-full {{ $barra }}"
                              style="width: {{ $aTerra ? 100 : max(4, round($frazione * 100)) }}%"></span>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between text-xs text-muted">
                    <span class="flex items-center gap-1.5">
                        <x-icona :is="\App\Enums\Icon::Gold" class="h-4 w-4" />
                        <span class="font-semibold text-fg">{{ $pg->gp }}</span> mo
                    </span>
                    <span class="flex items-center gap-1.5">
                        <x-icona :is="\App\Enums\Icon::ArmorClass" class="h-4 w-4" />
                        CA <span class="font-semibold text-fg">{{ $pg->armorClass() }}</span>
                    </span>
                </div>
            </a>
        @endforeach
    </div>
@endif
