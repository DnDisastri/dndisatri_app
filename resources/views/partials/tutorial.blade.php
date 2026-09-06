@php
    use App\Enums\Icon;
@endphp

{{-- <dialog> nativo: apertura, scorrimento e tab stanno in app.js; Esc e fondo
     scuro li gestisce il browser. I passi arrivano dal database. --}}
<dialog id="tutorial" aria-label="Tutorial"
        class="h-[85vh] max-h-[680px] w-[92vw] max-w-md overflow-hidden rounded-card border-0 bg-accent-soft p-0 text-on-accent-soft">
    <div class="flex h-full flex-col">

        <div class="flex items-center gap-2 border-b border-current/15 px-3 py-2.5">
            <div class="flex flex-1 gap-1.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($passi as $i => $passo)
                    <button type="button" data-capitolo="{{ $i + 1 }}"
                            title="{{ $passo->title }}" aria-label="Capitolo {{ $i + 1 }}: {{ $passo->title }}"
                            @if ($i === 0) aria-current="true" @endif
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold
                                   transition bg-current/10 hover:bg-current/20
                                   aria-[current]:bg-primary aria-[current]:text-on-primary">
                        {{ $i + 1 }}
                    </button>
                @endforeach
            </div>

            <button type="button" data-close-tutorial aria-label="Chiudi"
                    class="shrink-0 rounded-full p-1 opacity-70 transition hover:opacity-100">
                <x-icona :is="Icon::Close" class="h-5 w-5" />
            </button>
        </div>

        <div id="tutorial-slider" class="flex flex-1 snap-x snap-mandatory overflow-x-auto scroll-smooth">
            @foreach ($passi as $i => $passo)
                <section @class([
                    'flex h-full w-full shrink-0 snap-center snap-always flex-col overflow-y-auto p-6',
                    'items-center justify-center text-center' => $loop->last,
                ])>
                    <div @class(['mb-5', 'flex justify-center' => $loop->last])>
                        <x-tutorial-illustrazione :quale="$passo->illustration" />
                    </div>

                    <h3 @class(['text-xl', 'text-2xl' => $loop->last])>{{ $passo->title }}</h3>

                    <x-tutorial-testo :testo="$passo->body"
                                      @class(['mt-3 text-sm opacity-80', 'max-w-xs' => $loop->last]) />

                    @if ($loop->last)
                        <x-button variant="primary" type="button" data-close-tutorial class="mt-6">
                            Ho capito
                        </x-button>
                    @endif
                </section>
            @endforeach
        </div>

        <div class="flex items-center justify-between border-t border-current/15 px-4 py-2.5">
            <button type="button" data-tutorial-prev aria-label="Capitolo precedente"
                    class="flex items-center gap-1 rounded-full px-3 py-1.5 text-sm font-semibold opacity-70 transition hover:opacity-100 disabled:opacity-30">
                <x-icona :is="Icon::Back" class="h-5 w-5" /> Indietro
            </button>
            <button type="button" data-tutorial-next aria-label="Capitolo successivo"
                    class="flex items-center gap-1 rounded-full px-3 py-1.5 text-sm font-semibold opacity-70 transition hover:opacity-100 disabled:opacity-30">
                Avanti <x-icona :is="Icon::GoTo" class="h-5 w-5" />
            </button>
        </div>
    </div>
</dialog>
