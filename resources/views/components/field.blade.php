@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'autocomplete' => null,
    'required' => false,
])

@php $password = $type === 'password'; @endphp

<div>
    <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-fg">
        {{ $label }}
    </label>

    <div class="relative">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($required) required @endif
            {{ $attributes->merge([
                'class' => 'w-full rounded-md border border-line bg-page px-3 py-2 text-fg
                            placeholder:text-muted focus:border-active focus:outline-none'
                            .($password ? ' pr-10' : ''),
            ]) }}
        >

        @if ($password)
            <button type="button" data-toggle-password
                    aria-label="Mostra password"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-muted transition hover:text-fg">
                <x-icona :is="\App\Enums\Icon::ShowPassword" class="h-5 w-5" data-eye />
                <x-icona :is="\App\Enums\Icon::HidePassword" class="hidden h-5 w-5" data-eye-closed />
            </button>
        @endif
    </div>

    @error($name)
        <p class="mt-1 text-sm text-on-danger-soft">{{ $message }}</p>
    @enderror
</div>
