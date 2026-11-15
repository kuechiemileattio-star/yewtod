<div>
    <div class="mb-4 max-w-xs">
        <label for="event" class="field-label">Type d'événement</label>
        <select id="event" wire:model.live="event" class="field-input">
            <option value="">Tous</option>
            @foreach ($events as $e)<option value="{{ $e }}">{{ $e }}</option>@endforeach
        </select>
    </div>
    <ul class="space-y-2" role="list">
        @forelse ($entries as $entry)
            <li class="card grid gap-1 p-3 text-sm sm:grid-cols-[10rem_minmax(0,1fr)_minmax(0,1fr)] sm:items-center">
                <time class="text-muted" datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->setTimezone(config('app.display_timezone'))->format('d/m/Y H:i:s') }}</time>
                <p class="font-mono font-semibold">{{ $entry->event }}</p>
                <p class="truncate text-muted">{{ $entry->causer?->name ?? 'anonyme' }}
                    @if ($entry->subject_type) · {{ class_basename($entry->subject_type) }} #{{ $entry->subject_id }}@endif
                    @if ($entry->properties->get('email')) · {{ $entry->properties->get('email') }}@endif
                </p>
            </li>
        @empty
            <li class="card p-6 text-center text-muted">Aucun événement.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $entries->links() }}</div>
</div>
