@php $quanti = count($scontro->combatants ?? []); @endphp

<a href="{{ route('encounters.show', $scontro) }}"
   class="flex flex-wrap items-center justify-between gap-2 rounded-card border border-line bg-surface px-4 py-3 transition hover:border-active">
    <span class="min-w-0">
        <span class="block font-semibold text-fg">{{ $scontro->title }}</span>
        <span class="block text-xs text-muted">
            {{ $quanti }} {{ $quanti === 1 ? 'combattente' : 'combattenti' }}
            @if ($scontro->status !== App\Enums\EncounterStatus::Prepared) · round {{ $scontro->round }} @endif
            @if ($scontro->session) · {{ $scontro->session->numberLabel() }} @endif
        </span>
    </span>
    <x-badge :tone="$scontro->status->tone()">{{ $scontro->status->label() }}</x-badge>
</a>
