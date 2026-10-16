@props(['name', 'max', 'value' => ''])

{{-- Una textarea col contatore dei caratteri. Il limite lo tiene anche il server:
     qui serve a vederlo arrivare mentre si scrive. --}}
<div x-data="{ n: {{ mb_strlen((string) $value) }} }">
    <textarea name="{{ $name }}" maxlength="{{ $max }}" x-on:input="n = $el.value.length"
              {{ $attributes }}>{{ $value }}</textarea>
    <p class="mt-1 text-right text-xs text-muted" x-bind:class="n > {{ $max }} * 0.9 && 'text-on-danger-soft'">
        <span x-text="n">{{ mb_strlen((string) $value) }}</span> / {{ number_format($max, 0, ',', '.') }}
    </p>
</div>
