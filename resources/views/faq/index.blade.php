@extends('layouts.app')
@section('title', 'FAQs')

@section('content')

@php use App\Enums\Icon; @endphp

<x-pagina larghezza="stretta">
    <h2 class="mb-1 flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="Icon::Faq" class="h-7 w-7" /> FAQs
    </h2>
    <x-intro :page="\App\Enums\IntroPage::Faq" class="mb-4" />

    {{-- Apre il <dialog> del tutorial; niente pulsante senza passi. --}}
    @if ($passi->isNotEmpty())
        <x-button variant="primary" size="lg" full type="button" data-open-tutorial class="mb-8">
            <x-icona :is="Icon::Faq" class="h-5 w-5" />
            Leggi il tutorial
        </x-button>
    @endif

    @forelse ($gruppi as $categoria => $voci)
        <section class="mt-8 first:mt-0">
            @if ($categoria !== '')
                <h3 class="mb-3 text-lg font-semibold text-fg">{{ $categoria }}</h3>
            @endif

            <div class="space-y-2">
                @foreach ($voci as $voce)
                    {{-- <details>: apre e chiude senza JavaScript. --}}
                    <details class="group overflow-hidden rounded-card border border-line bg-surface">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3
                                        [&::-webkit-details-marker]:hidden">
                            <span class="font-medium text-fg">{{ $voce->question }}</span>
                            <x-icona :is="Icon::Expand"
                                     class="h-5 w-5 shrink-0 text-muted transition group-open:rotate-180" />
                        </summary>

                        {{-- Blade esegue l'escape del testo; `whitespace-pre-line` conserva gli a capo. --}}
                        <div class="whitespace-pre-line px-4 pb-4 text-sm text-muted">{{ $voce->answer }}</div>
                    </details>
                @endforeach
            </div>
        </section>
    @empty
        <x-empty size="lg">La guida non è ancora pronta.</x-empty>
    @endforelse

    <x-back :href="route('home')">Torna alla Home</x-back>
</x-pagina>

@if ($passi->isNotEmpty())
    @include('partials.tutorial')
@endif
@endsection
