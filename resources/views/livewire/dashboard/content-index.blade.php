<div>
    <x-page-header :title="$heading" subtitle="Brouillons, relectures, programmations et publications.">
        <x-slot:actions>
            @if ($type === null)
                <label for="newType" class="sr-only">Type à créer</label>
                <select id="newType" wire:model="newType" class="field-input w-auto">
                    <option value="">Type à créer…</option>
                    @foreach ($otherTypes as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                </select>
            @endif
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="size-4" /> Nouveau contenu</button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <label class="relative block">
            <span class="sr-only">Rechercher</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher un titre" class="field-input pl-9" maxlength="100">
        </label>
        <label><span class="sr-only">Statut</span>
            <select wire:model.live="status" class="field-input">
                <option value="">Tous les statuts</option>
                @foreach ($statuses as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
            </select>
        </label>
        @if ($type === null)
            <label><span class="sr-only">Type</span>
                <select wire:model.live="filterType" class="field-input">
                    <option value="">Tous les types</option>
                    @foreach ($otherTypes as $t)<option value="{{ $t->value }}">{{ $t->plural() }}</option>@endforeach
                </select>
            </label>
        @endif
        <label><span class="sr-only">Tri</span>
            <select wire:model.live="sort" class="field-input">
                <option value="recent">Modifiés récemment</option><option value="ancien">Plus anciens</option><option value="titre">Titre A–Z</option>
            </select>
        </label>
    </div>

    @if ($selected !== [])
        <div class="card mb-4 flex flex-wrap items-center gap-2 p-3" role="region" aria-label="Actions groupées">
            <p class="mr-auto text-sm font-semibold">{{ count($selected) }} sélectionné(s)</p>
            @if ($canPublish)
                <button wire:click="bulk('publish')" class="btn-secondary">Publier</button>
                <button wire:click="bulk('unpublish')" class="btn-secondary">Dépublier</button>
                <button wire:click="bulk('archive')" class="btn-secondary">Archiver</button>
            @endif
            <x-confirm-action method="bulk" arg="delete" label="Supprimer" title="Supprimer les contenus sélectionnés ?" confirm="Supprimer">Cette action est définitive.</x-confirm-action>
        </div>
    @endif

    @if ($contents->isEmpty())
        <x-empty-state title="Aucun contenu" icon="article">Aucun contenu ne correspond à ces filtres.
            <x-slot:action><button type="button" wire:click="create" class="btn-primary">Créer un contenu</button></x-slot:action>
        </x-empty-state>
    @else
        <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" role="list">
            @foreach ($contents as $c)
                <li class="card flex flex-col overflow-hidden" wire:key="c-{{ $c->id }}">
                    <button type="button" wire:click="preview({{ $c->id }})" class="block aspect-[16/9] w-full overflow-hidden"><span class="sr-only">Aperçu de {{ $c->title }}</span>
                        <x-content-cover :content="$c" sizes="(min-width: 1280px) 25vw, (min-width: 640px) 50vw, 100vw" />
                    </button>
                    <div class="flex flex-1 flex-col p-4">
                        <div class="mb-2 flex flex-wrap items-center gap-2">
                            <x-status-badge :status="$c->status" />
                            @if ($type === null)<span class="badge bg-surface-2 text-muted">{{ $c->type->label() }}</span>@endif
                            @if ($c->status->value === 'scheduled')<span class="text-xs text-muted">le {{ $c->scheduled_at?->setTimezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</span>@endif
                        </div>
                        <h2 class="mb-1 line-clamp-2 font-serif text-lg font-bold">{{ $c->title }}</h2>
                        <p class="mb-4 text-xs text-muted">{{ $c->author?->name ?? 'Auteur supprimé' }} · modifié {{ $c->updated_at->diffForHumans() }}</p>
                        <div class="mt-auto flex flex-wrap items-center gap-1">
                            <label class="mr-auto flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" value="{{ $c->id }}" wire:model.live="selected" class="size-5 rounded border-line-strong"> Sélectionner</label>
                            <a href="{{ route('dashboard.contents.edit', ['type' => $c->type->value, 'content' => $c->id]) }}" class="btn-ghost px-3"><x-icon name="edit" class="size-4" /> Modifier</a>
                            @if ($c->isPubliclyVisible())
                                <a href="{{ $c->publicUrl() }}" target="_blank" rel="noopener" class="btn-ghost min-w-11 px-2" aria-label="Voir la page publique"><x-icon name="eye" class="size-4" /></a>
                            @endif
                            <x-confirm-action method="delete" :arg="$c->id" label="Supprimer" title="Supprimer « {{ \Illuminate\Support\Str::limit($c->title, 60) }} » ?" confirm="Supprimer" button-class="btn-ghost px-3 text-danger">
                                Le contenu et ses fichiers seront supprimés définitivement.
                            </x-confirm-action>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $contents->links() }}</div>
    @endif

    <x-modal :show="$previewed !== null" :title="$previewed?->title ?? ''">
        @if ($previewed)
            <div class="mb-4 aspect-[16/9] overflow-hidden rounded-xl"><x-content-cover :content="$previewed" /></div>
            <p class="mb-2 text-sm text-muted">{{ $previewed->type->label() }} · {{ $previewed->displayAuthor() }} · {{ $previewed->displayDate()->translatedFormat('j F Y') }}</p>
            <p>{{ $previewed->excerpt(400) }}</p>
        @endif
        <x-slot:footer>
            @if ($previewed)
                <a href="{{ route('dashboard.contents.edit', ['type' => $previewed->type->value, 'content' => $previewed->id]) }}" class="btn-primary">Modifier</a>
            @endif
        </x-slot:footer>
    </x-modal>
</div>
