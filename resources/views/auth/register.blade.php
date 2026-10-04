@extends('layouts.guest')
@section('title', 'Registrazione')

@section('top')
    <x-back dove="sopra" :href="route('home')">Torna indietro</x-back>
@endsection

@section('content')
    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-4">
        @csrf

        <x-field name="name" label="Nome utente" required autofocus />
        <x-field name="email" label="Email" type="email" autocomplete="email" required />
        <x-field name="password" label="Password" type="password" autocomplete="new-password" required />
        <x-field name="password_confirmation" label="Ripeti la password" type="password"
                 autocomplete="new-password" required />

        <fieldset>
            <legend class="mb-1 block text-sm font-medium text-fg">Hai già fatto sessioni con noi?</legend>

            <div class="grid grid-cols-2 gap-2">
                @foreach (['1' => 'Sì', '0' => 'No'] as $valore => $etichetta)
                    <label class="flex cursor-pointer items-center justify-center rounded-full border border-line px-3 py-2
                                  font-semibold text-fg transition
                                  has-[:checked]:border-active has-[:checked]:bg-active has-[:checked]:text-on-active
                                  has-[:focus-visible]:outline has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-active">
                        <input type="radio" name="played_before" value="{{ $valore }}" required class="sr-only"
                               @checked(old('played_before') === $valore)>
                        {{ $etichetta }}
                    </label>
                @endforeach
            </div>

            @error('played_before')
                <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
            @enderror
        </fieldset>

        <div>
            <label for="discovery_source" class="mb-1 block text-sm font-medium text-fg">
                Come ci hai conosciuti? <span class="font-normal text-muted">(facoltativo)</span>
            </label>
            <textarea id="discovery_source" name="discovery_source" rows="2" maxlength="500"
                      placeholder="Un amico, un evento, i social…"
                      class="w-full rounded-md border border-line bg-page px-3 py-2 text-fg placeholder:text-muted
                             focus:border-active focus:outline-none">{{ old('discovery_source') }}</textarea>

            @error('discovery_source')
                <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
            @enderror
        </div>

        <x-button size="lg">Crea account <x-icona :is="\App\Enums\Icon::GoTo" class="h-5 w-5" /></x-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        Hai già un account?
        <a href="{{ route('login') }}" class="font-semibold text-fg hover:underline">Accedi</a>
    </p>
@endsection
