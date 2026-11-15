{{-- Notifications toast : messages flash du serveur + événements Livewire « toast ». --}}
@php
    $initial = collect([
        session('status') ? ['type' => 'success', 'message' => session('status')] : null,
        session('toast') ? session('toast') : null,
        session('error') ? ['type' => 'error', 'message' => session('error')] : null,
    ])->filter()->values();
@endphp
<div x-data="toasts" data-initial='@json($initial)' class="pointer-events-none fixed inset-x-0 bottom-0 z-[60] flex flex-col items-center gap-2 p-4 sm:items-end" aria-live="polite" role="status">
    <template x-for="item in items" x-bind:key="item.id">
        <div class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border border-line bg-surface p-4 text-sm shadow-lg">
            <span class="mt-0.5 inline-block size-2.5 shrink-0 rounded-full" x-bind:class="item.type === 'error' ? 'bg-danger' : 'bg-success'"></span>
            <p class="flex-1" x-text="item.message"></p>
            <button type="button" class="-m-2 min-h-11 min-w-11 text-muted hover:text-ink" x-bind:data-id="item.id" x-on:click="dismiss" aria-label="Fermer">×</button>
        </div>
    </template>
</div>
