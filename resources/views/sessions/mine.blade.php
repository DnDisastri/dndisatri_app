@extends('layouts.app')
@section('title', 'Le mie prenotazioni')

@section('content')
@php
    use App\Enums\Icon;

    $niente = $daConfermare->isEmpty() && $domandeRiserva->isEmpty() && $confermate->isEmpty()
        && $inviate->isEmpty() && $riserve->isEmpty() && $scadute->isEmpty();
@endphp

<x-pagina class="mx-auto max-w-3xl space-y-6">
    <div>
        <h2 class="flex items-center gap-2 text-2xl text-fg">
            <x-icona :is="Icon::Bookings" class="h-7 w-7" /> Le mie prenotazioni
        </h2>
        <p class="mt-1 text-sm text-muted">Le sessioni a cui hai chiesto un posto, da quelle che aspettano una tua risposta.</p>
    </div>

    @if ($niente)
        <x-empty>
            Non hai ancora chiesto un posto.
            <a href="{{ route('sessions.index') }}" class="font-semibold text-active hover:underline">Guarda le sessioni del mese</a>
        </x-empty>
    @endif

    {{-- Prima quello che aspetta una risposta: si risponde da qui, senza aprire la sessione. --}}
    @if ($daConfermare->isNotEmpty())
        <section class="space-y-2">
            <h3 class="text-xs uppercase tracking-wide text-muted">C'è un posto per te: confermalo</h3>
            @foreach ($daConfermare as $posto)
                @include('sessions.partials.mia-prenotazione', ['posto' => $posto, 'nota' => 'Confermi entro '.$posto->offer_expires_at->translatedFormat('l j F \a\l\l\e H:i').'.'])
            @endforeach
        </section>
    @endif

    @if ($domandeRiserva->isNotEmpty())
        <section class="space-y-2">
            <h3 class="text-xs uppercase tracking-wide text-muted">Sessione piena: resti fra le riserve?</h3>
            @foreach ($domandeRiserva as $posto)
                @include('sessions.partials.mia-prenotazione', ['posto' => $posto, 'nota' => 'Se qualcuno si ritira potresti essere chiamato, ma non è garantito.'])
            @endforeach
        </section>
    @endif

    @foreach ([
        'Confermate' => $confermate,
        'Richieste inviate' => $inviate,
        'Fra le riserve' => $riserve,
        'Conferma scaduta' => $scadute,
    ] as $titolo => $elenco)
        @if ($elenco->isNotEmpty())
            <section class="space-y-2">
                <h3 class="text-xs uppercase tracking-wide text-muted">{{ $titolo }}</h3>
                @foreach ($elenco as $posto)
                    @include('sessions.partials.mia-prenotazione', ['posto' => $posto, 'nota' => null])
                @endforeach
            </section>
        @endif
    @endforeach

    <x-back :href="route('sessions.index')">Torna alle sessioni</x-back>
</x-pagina>

@include('partials.conferma')
@endsection
