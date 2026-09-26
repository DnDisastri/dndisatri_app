{{--
    Cosa vuol dire la difficoltà di una quest: àncora ognuna ai gradi
    d'avventuriero, così «Difficile» diventa un intervallo di livelli, non un
    aggettivo. Le fasce si accavallano di un gradino apposta.
--}}
<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @foreach (\App\Enums\QuestDifficulty::cases() as $difficolta)
        @php [$da, $a] = $difficolta->ranks(); @endphp
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
            <x-badge tone="accent" class="shrink-0">{{ $difficolta->label() }}</x-badge>
            <span class="text-sm font-medium text-fg">
                {{ $da->label() }} → {{ $a->label() }}
            </span>
            <span class="text-xs text-muted">liv. {{ $difficolta->suggestedLevels() }}</span>
            <p class="w-full text-xs text-muted">{{ $difficolta->description() }}</p>
        </div>
    @endforeach
</div>
