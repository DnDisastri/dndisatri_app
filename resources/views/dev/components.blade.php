@extends('layouts.app')
@section('title', 'La vetrina')

@section('content')
{{-- Vetrina disponibile solo fuori produzione, renderizzata nel layout reale per verificare tema e varianti dei componenti. --}}
@php
    $varianti = [
        'primary' => 'Primario: l\'azione principale, una per schermata',
        'secondary' => 'Secondario: un\'azione pari, che non è però quella',
        'quiet' => 'Quiet: annulla, torna indietro, il servizio',
    ];
    $misure = ['sm' => 'sm', 'md' => 'md (predefinita)', 'lg' => 'lg'];
    $toni = ['neutral' => 'neutral', 'accent' => 'accent', 'danger' => 'danger'];
@endphp

<div class="mx-auto max-w-3xl space-y-8 px-4 py-6">

    <div>
        <h2 class="text-2xl text-fg">La vetrina</h2>
        <p class="mt-1 text-sm text-muted">
            Tutte le varianti dei componenti, una sotto l'altra. Questa pagina
            esiste solo fuori produzione.
        </p>
    </div>

    <x-panel title="Pulsanti">
        <div class="space-y-5">
            @foreach ($varianti as $variante => $nome)
                <div>
                    <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">{{ $nome }}</p>

                    <div class="flex flex-wrap items-center gap-3">
                        @foreach ($misure as $misura => $etichetta)
                            <x-button :variant="$variante" :size="$misura" type="button">
                                {{ $etichetta }}
                            </x-button>
                        @endforeach

                        <x-button :variant="$variante" type="button" disabled>Spento</x-button>
                    </div>
                </div>
            @endforeach

            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">Con un'icona</p>
                <div class="flex flex-wrap items-center gap-3">
                    <x-button type="button">
                        <x-icona :is="\App\Enums\Icon::Approve" class="h-4 w-4" /> Conferma
                    </x-button>
                    <x-button variant="quiet" type="button">
                        Vai <x-icona :is="\App\Enums\Icon::GoTo" class="h-4 w-4" />
                    </x-button>
                </div>
            </div>

            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">A tutta larghezza</p>
                <x-button full type="button">Manda la richiesta</x-button>
            </div>

            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">In coppia</p>
                <div class="flex gap-3">
                    <x-button size="lg" class="flex-1" type="button">Salva</x-button>
                    <x-button size="lg" variant="quiet" class="flex-1" href="#">Annulla</x-button>
                </div>
            </div>

            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">Come collegamento</p>
                <x-button href="#">Porta da un'altra parte</x-button>
            </div>
        </div>
    </x-panel>

    <x-panel title="Pillole">
        <div class="space-y-5">
            @foreach (['sm' => 'Misura sm (predefinita)', 'md' => 'Misura md'] as $misura => $etichetta)
                <div>
                    <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">{{ $etichetta }}</p>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($toni as $tono => $nome)
                            <x-badge :tone="$tono" :size="$misura">{{ $nome }}</x-badge>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">Come si usano davvero</p>
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge tone="accent">Difficile</x-badge>
                    <x-badge>Conclusa</x-badge>
                    <x-badge tone="danger">Caduto</x-badge>
                </div>
            </div>
        </div>
    </x-panel>

    <x-panel title="Riquadri">
        <div class="space-y-5">
            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">Imbottitura</p>
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-card padding="sm">Stretta (<code>sm</code>)</x-card>
                    <x-card>Normale, la predefinita</x-card>
                    <x-card padding="lg">Larga (<code>lg</code>)</x-card>
                </div>
            </div>

            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">Ferma e cliccabile</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-card>Ferma: nessun bordo che si accende</x-card>
                    <x-card href="#">Cliccabile: si accende al passaggio</x-card>
                </div>
            </div>

            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">
                    A filo, per chi comincia con un'immagine
                </p>
                <x-card flush padding="none" href="#" class="max-w-xs">
                    <span class="flex aspect-video w-full items-center justify-center bg-primary text-on-primary">
                        un'immagine
                    </span>
                    <span class="block p-4 text-sm text-fg">
                        L'imbottitura sta dentro il contenuto, non nella scatola.
                    </span>
                </x-card>
            </div>

            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">Un riquadro dentro un riquadro</p>
                <x-card>
                    <p class="mb-2 text-sm text-fg">La card sta sopra la pagina, l'inset rientra.</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-inset><span class="text-sm text-fg">Normale</span></x-inset>
                        <x-inset padding="sm"><span class="text-sm text-fg">Stretto (<code>sm</code>)</span></x-inset>
                    </div>
                </x-card>
            </div>
        </div>
    </x-panel>

    <x-panel title="Il manifesto">
        <p class="mb-3 text-sm text-muted">
            Tutta immagine, con il testo sopra. L'angolo in basso a destra è
            vivo e gli altri tre sono tondi: è il segno che lo distingue da
            tutte le altre card. Senza immagine ricade sul navy.
        </p>

        <div class="flex gap-4 overflow-x-auto pb-2">
            <x-poster href="#" label="Nuovo evento" meta="il 26/05/26"
                      title="Tutte le facce dei giochi di ruolo"
                      class="w-72 shrink-0" />

            <x-poster href="#" label="Nuovo evento" meta="il 3/09/26"
                      title="Un titolo corto"
                      action="Vai a vedere"
                      class="w-72 shrink-0" />
        </div>
    </x-panel>

    <x-panel title="Quando non c'è niente">
        <div class="space-y-5">
            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">
                    Una sezione vuota, dentro una pagina che ha dell'altro
                </p>
                <x-empty>Nessun tavolo in programma.</x-empty>
            </div>

            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">
                    Una pagina intera vuota: <code>size="lg"</code>
                </p>
                <x-empty size="lg">Non c'è ancora nessuna campagna.</x-empty>
            </div>
        </div>
    </x-panel>

    <x-panel title="Messaggi">
        <div class="space-y-3">
            <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">
                Non è contenuto della pagina: è l'applicazione che parla
            </p>
            <x-note>Prenotato. Il posto è tuo quando il dungeon master conferma che la serata si fa.</x-note>
            <x-note tone="danger">Non hai abbastanza oro: te ne mancano 40.</x-note>
        </div>
    </x-panel>

    <x-panel title="Quello che c'era già">
        <div class="space-y-5">
            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">Valori della scheda</p>
                <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                    <x-stat label="Forza" value="16" note="+3" />
                    <x-stat label="CA" value="18" />
                    <x-stat label="PF" value="42" note="su 42" />
                </div>
            </div>

            <div>
                <p class="mb-2 mt-3 text-xs uppercase tracking-wide text-muted">Campo di modulo</p>
                <x-field name="vetrina_esempio" label="Nome del personaggio" />
            </div>
        </div>
    </x-panel>
</div>
@endsection
