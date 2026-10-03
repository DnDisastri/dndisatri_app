@extends('layouts.app')
@section('title', 'Segnala un problema')

@section('content')
<x-pagina larghezza="stretta" class="space-y-6">
    <x-back :href="$provenienza" dove="sopra">Torna dov'eri</x-back>

    <h2 class="flex items-center gap-2 text-2xl text-fg">
        <x-icona :is="\App\Enums\Icon::BugReports" class="h-7 w-7" /> Segnala un problema
    </h2>

    <x-panel title="Cos'è successo">
        <form method="POST" action="{{ route('bug-reports.store') }}" class="flex flex-col gap-4">
            @csrf

            {{-- La pagina di partenza viaggia col modulo: serve al ritorno e a chi legge la segnalazione. --}}
            <input type="hidden" name="page" value="{{ $provenienza }}">

            <x-field name="title" label="In una riga" required
                     placeholder="Il pulsante per vendere non fa niente" />

            <div>
                <label for="description" class="mb-1 block text-sm font-medium text-fg">
                    Raccontalo per bene
                </label>

                <textarea id="description" name="description" rows="6" required
                          placeholder="Cosa stavi facendo, cosa ti aspettavi, cosa è successo invece."
                          class="w-full rounded-md border border-line bg-page px-3 py-2 text-fg
                                 placeholder:text-muted focus:border-active focus:outline-none"
                >{{ old('description') }}</textarea>

                @error('description')
                    <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
                @enderror
            </div>

            <p class="text-xs text-muted">
                La pagina da cui arrivi e il browser che stai usando li alleghiamo
                noi: servono per capire il problema e non devi cercarli tu.
            </p>

            <x-button class="self-start">Invia</x-button>
        </form>
    </x-panel>
</x-pagina>
@endsection
