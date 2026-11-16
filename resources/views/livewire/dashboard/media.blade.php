<div>
    <x-page-header title="Médiathèque" subtitle="Images (converties en WebP, plusieurs tailles) et PDF publics, 20 Mo maximum.">
        <x-slot:actions>
            <x-confirm-action method="deleteOrphans" label="Supprimer les fichiers inutilisés" title="Supprimer les fichiers inutilisés ?" confirm="Supprimer" button-class="btn-secondary">
                Les fichiers de plus de 24 heures qui ne sont utilisés par aucun contenu, livre, page ou réglage seront supprimés.
            </x-confirm-action>
        </x-slot:actions>
    </x-page-header>

    <label for="m-upload" class="card mb-6 flex min-h-28 cursor-pointer flex-col items-center justify-center gap-2 border-2 border-dashed border-line-strong p-6 text-center hover:bg-surface-2">
        <x-icon name="upload" class="size-7 text-muted" />
        <span class="font-semibold">Ajouter un fichier</span>
        <span class="text-sm text-muted">JPEG, PNG, WebP, GIF, AVIF ou PDF — pas de SVG</span>
        <input id="m-upload" type="file" wire:model="upload" accept="image/jpeg,image/png,image/webp,image/gif,image/avif,application/pdf" class="sr-only">
        <span wire:loading wire:target="upload" class="text-sm text-muted">Envoi en cours…</span>
    </label>

    <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <label class="relative block"><span class="sr-only">Rechercher</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nom ou texte alternatif" class="field-input pl-9" maxlength="100">
        </label>
        <label><span class="sr-only">Type</span>
            <select wire:model.live="kind" class="field-input"><option value="">Tous les fichiers</option><option value="image">Images</option><option value="document">Documents PDF</option></select>
        </label>
    </div>

    @if ($items->isEmpty())
        <x-empty-state icon="image" title="Médiathèque vide">Ajoutez une image ou un PDF ci-dessus.</x-empty-state>
    @else
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6">
            @foreach ($items as $m)
                <li wire:key="m-{{ $m->id }}">
                    <button type="button" wire:click="select({{ $m->id }})" class="card group block w-full overflow-hidden text-left">
                        <span class="flex aspect-square items-center justify-center bg-surface-2">
                            @if ($m->kind === 'image')
                                <img src="{{ $m->url() }}" srcset="{{ $m->srcset() }}" sizes="200px" alt="{{ $m->alt ?? '' }}" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <x-icon name="report" class="size-10 text-muted" />
                            @endif
                        </span>
                        <span class="block truncate p-2 text-xs">{{ $m->original_name }}@unless ($m->alt || $m->kind !== 'image') <span class="text-danger">· sans texte alternatif</span>@endunless</span>
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $items->links() }}</div>
    @endif

    <x-modal :show="$selected !== null" :title="$selected?->original_name ?? ''" max-width="max-w-3xl">
        @if ($selected)
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="flex items-center justify-center rounded-xl bg-surface-2 p-2">
                    @if ($selected->kind === 'image')<img src="{{ $selected->url() }}" alt="{{ $selected->alt ?? '' }}" class="max-h-72 w-auto rounded">@else<x-icon name="report" class="size-16 text-muted" />@endif
                </div>
                <div class="min-w-0 text-sm">
                    <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                        <dt class="text-muted">Type</dt><dd>{{ $selected->mime }}</dd>
                        <dt class="text-muted">Taille</dt><dd>{{ \App\Support\Format::fileSize($selected->size) }}</dd>
                        @if ($selected->width)<dt class="text-muted">Dimensions</dt><dd>{{ $selected->width }} × {{ $selected->height }} px</dd>@endif
                        <dt class="text-muted">Ajouté</dt><dd>{{ $selected->created_at->setTimezone(config('app.display_timezone'))->format('d/m/Y') }} par {{ $selected->uploader?->name ?? 'un membre supprimé' }}</dd>
                        <dt class="text-muted">Utilisé</dt><dd>{{ $selectedUsed ? 'Oui' : 'Non (orphelin)' }}</dd>
                    </dl>
                    <div x-data="copyText" data-text="{{ $selected->url() }}" class="mt-3">
                        <label class="field-label" for="m-url">Adresse (réutilisation)</label>
                        <div class="flex gap-2"><input id="m-url" readonly value="{{ $selected->url() }}" class="field-input min-w-0 flex-1 text-xs"><button type="button" x-on:click="copy" class="btn-secondary"><span x-show="!copied">Copier</span><span x-show="copied" x-cloak>Copié</span></button></div>
                    </div>
                    @if ($selected->kind === 'image')
                        <form wire:submit="saveAlt" class="mt-3">
                            <label for="m-alt" class="field-label">Texte alternatif</label>
                            <input id="m-alt" wire:model="alt" maxlength="300" class="field-input" placeholder="Décrivez l'image pour les lecteurs d'écran">
                            <button class="btn-primary mt-2">Enregistrer</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
        <x-slot:footer>
            @if ($selected)
                <x-confirm-action method="delete" :arg="$selected->id" label="Supprimer" title="Supprimer ce fichier ?" confirm="Supprimer" button-class="btn-danger">@if ($selectedUsed)Attention : ce fichier est utilisé quelque part sur le site.@else Le fichier sera supprimé définitivement.@endif</x-confirm-action>
            @endif
        </x-slot:footer>
    </x-modal>
</div>
