{{-- Boîte de confirmation (cahier § 10) ; à placer dans un élément x-data="confirmDialog" x-ref="form". --}}
@props(['title', 'confirm' => 'Confirmer'])
<div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center" role="dialog" aria-modal="true" x-on:keydown.escape.window="cancel">
    <div class="card w-full max-w-md p-6 shadow-xl" x-on:click.outside="cancel">
        <h2 class="mb-2 text-lg font-bold">{{ $title }}</h2>
        <div class="mb-6 text-sm text-muted">{{ $slot }}</div>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="btn-secondary" x-ref="cancel" x-on:click="cancel">Annuler</button>
            <button type="button" class="btn-danger" x-on:click="accept">{{ $confirm }}</button>
        </div>
    </div>
</div>
