{{--
    Cosa vuol dire la difficoltà di una quest (richiesta 10).

    Le quattro parole — Facile, Media, Difficile, Epica — non dicono niente da
    sole a chi arriva ora: «media» rispetto a cosa? Qui si àncora ognuna ai
    gradi d'avventuriero (richiesta 8), così «Difficile» smette di essere un
    aggettivo e diventa «da Professionista a Maestro, liv. 5–12».

    Le fasce si accavallano di un gradino apposta: una quest è per un tratto
    della scalata, non per un livello preciso.
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
