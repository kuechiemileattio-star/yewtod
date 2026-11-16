<div>
    <x-page-header title="Modération" subtitle="Avis des lecteurs : la modération change le statut, jamais le texte." />
    <div class="-mx-4 mb-6 overflow-x-auto border-b border-line px-4 sm:mx-0 sm:px-0" role="tablist" aria-label="Statut des avis">
        <div class="flex min-w-max gap-1">
            @foreach (\App\Models\Review::STATUSES as $key => $label)
                <button type="button" role="tab" wire:click="$set('status', '{{ $key }}')" aria-selected="{{ $current === $key ? 'true' : 'false' }}"
                    @class(['min-h-11 border-b-2 px-4 text-sm font-semibold', 'border-accent text-ink' => $current === $key, 'border-transparent text-muted hover:text-ink' => $current !== $key])>
                    {{ $label }} <span class="badge ml-1 bg-surface-2 text-muted">{{ $counts[$key] ?? 0 }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @if ($reviews->isEmpty())
        <x-empty-state icon="shield" title="Rien à modérer">Aucun avis dans cette catégorie.</x-empty-state>
    @else
        <ul class="space-y-4">
            @foreach ($reviews as $review)
                <li class="card p-4 sm:p-5" wire:key="r-{{ $review->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">
                                @if ($review->reviewable && method_exists($review->reviewable, 'publicUrl'))
                                    <a class="link" href="{{ $review->reviewable->publicUrl() }}" target="_blank" rel="noopener">{{ \App\Livewire\Dashboard\Moderation::targetLabel($review) }}</a>
                                @else
                                    {{ \App\Livewire\Dashboard\Moderation::targetLabel($review) }}
                                @endif
                            </p>
                            <p class="text-xs text-muted">Par <strong class="text-ink">{{ $review->name }}</strong> · {{ $review->created_at->setTimezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</p>
                        </div>
                        <span class="badge bg-surface-2 text-muted">{{ $review->statusLabel() }}</span>
                    </div>
                    <p class="mt-3 whitespace-pre-line break-words">{{ $review->body }}</p>
                    @if ($review->moderated_at)
                        <p class="mt-2 text-xs text-muted">Décision de {{ $review->moderator?->name ?? 'un membre supprimé' }} le {{ $review->moderated_at->setTimezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</p>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($review->status !== 'approved')<button type="button" wire:click="approve({{ $review->id }})" class="btn-primary"><x-icon name="check" class="size-4" /> Approuver</button>@endif
                        @if ($review->status !== 'rejected')<button type="button" wire:click="reject({{ $review->id }})" class="btn-secondary">Rejeter</button>@endif
                        <x-confirm-action method="delete" :arg="$review->id" label="Supprimer" title="Supprimer cet avis ?" confirm="Supprimer" button-class="btn-ghost text-danger">L'avis sera supprimé définitivement.</x-confirm-action>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $reviews->links() }}</div>
    @endif
</div>
