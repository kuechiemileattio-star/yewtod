{{-- Onglets reflétés dans l'URL (?onglet=) ; défilement horizontal sur petit écran. --}}
@props(['tabs', 'current'])
<div class="-mx-4 mb-6 overflow-x-auto border-b border-line px-4 sm:mx-0 sm:px-0" role="tablist">
    <div class="flex min-w-max gap-1">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" aria-selected="{{ $current === $key ? 'true' : 'false' }}"
                @class(['min-h-11 border-b-2 px-4 text-sm font-semibold', 'border-accent text-ink' => $current === $key, 'border-transparent text-muted hover:text-ink' => $current !== $key])>{{ $label }}</button>
        @endforeach
    </div>
</div>
