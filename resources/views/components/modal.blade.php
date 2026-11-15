{{-- Fenêtre modale accessible, pilotée par une propriété Livewire booléenne (wire:model). --}}
@props(['show', 'title', 'maxWidth' => 'max-w-2xl'])
@if ($show)
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="modal-titre" wire:keydown.escape.window="closeModal">
        <div class="card flex max-h-[92dvh] w-full {{ $maxWidth }} flex-col rounded-b-none shadow-xl sm:rounded-b-2xl">
            <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3 sm:px-6">
                <h2 id="modal-titre" class="truncate text-lg font-bold">{{ $title }}</h2>
                <button type="button" wire:click="closeModal" class="btn-ghost min-w-11 px-2" aria-label="Fermer"><x-icon name="close" /></button>
            </div>
            <div class="overflow-y-auto px-4 py-4 sm:px-6">{{ $slot }}</div>
            @isset($footer)<div class="flex flex-col-reverse gap-2 border-t border-line px-4 py-3 sm:flex-row sm:justify-end sm:px-6">{{ $footer }}</div>@endisset
        </div>
    </div>
@endif
