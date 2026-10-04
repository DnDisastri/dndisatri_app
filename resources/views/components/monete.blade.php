@props([
    'valore' => null,
    'borsa' => null,
])

{{-- Un prezzo (`valore`, in rame) non mostra mai il platino; una borsa (`borsa`, Coins) sì. --}}
<span {{ $attributes->merge(['class' => 'whitespace-nowrap']) }}>{{ $borsa ? $borsa->format() : \App\Domain\Dnd\Coins::formatValue((int) $valore) }}</span>
