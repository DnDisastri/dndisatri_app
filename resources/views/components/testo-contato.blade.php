@props(['name', 'max', 'value' => ''])

{{-- Una textarea col contatore dei caratteri (lo aggiorna app.js). Il limite lo
     tiene anche il server: qui serve a vederlo arrivare mentre si scrive. --}}
<div data-contatore data-max="{{ $max }}">
    <textarea name="{{ $name }}" maxlength="{{ $max }}" {{ $attributes }}>{{ $value }}</textarea>
    <p class="mt-1 text-right text-xs text-muted" data-contatore-riga>
        <span data-contatore-n>{{ mb_strlen((string) $value) }}</span> / {{ number_format($max, 0, ',', '.') }}
    </p>
</div>
