@php
    /**
     * Il confronto fra com'è la scheda adesso e come diventerebbe.
     *
     * Si salva solo il diff, quindi il «prima» va letto dal personaggio in
     * questo momento: è anche il motivo per cui può risultare diverso da
     * quello che il giocatore vedeva quando ha proposto.
     */
    $record = $getRecord();
    $character = $record->character;
    $rows = $record->diffRows();
@endphp

<div class="space-y-3">
    @if ($record->isStale())
        <p class="rounded-md bg-danger-50 px-3 py-2 text-sm text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">
            La scheda è stata modificata dopo questa proposta. Controlla la colonna
            <strong>Adesso</strong> prima di approvare: quello che il giocatore vedeva
            poteva essere diverso.
        </p>
    @endif

    @forelse ($rows as $row)
        <div class="grid grid-cols-3 items-baseline gap-3 border-b border-gray-200 py-2 text-sm last:border-0 dark:border-white/10">
            <div class="font-medium text-gray-700 dark:text-gray-300">{{ $row['label'] }}</div>
            <div class="text-gray-500 line-through dark:text-gray-400">{{ $row['before'] }}</div>
            <div class="font-semibold text-success-600 dark:text-success-400">{{ $row['after'] }}</div>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Questa richiesta non modifica direttamente la scheda.
        </p>
    @endforelse

    @if ($rows->isNotEmpty())
        <div class="grid grid-cols-3 gap-3 pt-1 text-xs uppercase tracking-wide text-gray-400">
            <div>Campo</div>
            <div>Adesso</div>
            <div>Diventerebbe</div>
        </div>
    @endif

    @if ($record->grant_gp)
        <p class="text-sm">
            <span class="font-medium">Oro:</span>
            {{ $character?->gp ?? 0 }} mo
            <span class="text-gray-400">→</span>
            <span class="font-semibold text-success-600 dark:text-success-400">
                {{ ($character?->gp ?? 0) + $record->grant_gp }} mo
            </span>
            <span class="text-gray-500">({{ $record->grant_gp > 0 ? '+' : '' }}{{ $record->grant_gp }})</span>
        </p>
    @endif

    @foreach ($record->grant_items ?? [] as $item)
        <p class="text-sm text-success-600 dark:text-success-400">
            + {{ $item['qty'] ?? 1 }}× {{ $item['name'] }}
        </p>
    @endforeach
</div>
