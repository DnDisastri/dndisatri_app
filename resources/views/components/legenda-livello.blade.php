@props(['character' => null])

{{--
    Come si sale di livello: la regola del gruppo non sta nel codice (l'app non
    fa salire nessuno), quindi va scritta. Di norma una sessione = un livello,
    ma è una richiesta che approva il DM.

    Con un personaggio, la legenda conta anche le sessioni giocate dall'ultimo
    passaggio.
--}}
<x-note tone="neutral" {{ $attributes }}>
    <p class="font-semibold text-fg">Come si sale di livello</p>
    <p class="mt-1 text-sm">
        Di norma ogni sessione che giochi ti dà diritto a un livello. Non è
        automatico: quando pensi di averne diritto premi <span class="font-medium">«Sali
        di livello»</span>, e il passaggio lo approva un dungeon master.
    </p>

    @if ($character)
        @php $fatte = $character->sessionsSinceLastLevelUp(); @endphp
        <p class="mt-2 text-sm">
            @if ($fatte >= 1)
                Hai giocato <span class="font-semibold text-fg">{{ $fatte }}</span>
                {{ $fatte === 1 ? 'sessione' : 'sessioni' }} da quando sei salito
                di livello: <span class="font-medium text-fg">puoi chiedere il passaggio</span>.
            @else
                Non hai ancora giocato sessioni da quando sei salito di livello:
                di norma ne serve almeno una prima di chiedere il prossimo.
            @endif
        </p>
    @endif
</x-note>
