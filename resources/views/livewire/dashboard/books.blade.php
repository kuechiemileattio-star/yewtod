<div>
    <x-page-header title="Livres" subtitle="La bibliothèque commentée : fiches, avis, vente en euros, version électronique.">
        <x-slot:actions>
            <button type="button" wire:click="create" class="btn-primary"><x-icon name="plus" class="size-4" /> Nouveau livre</button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <label class="relative block">
            <span class="sr-only">Rechercher par titre ou auteur</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Titre ou auteur" class="field-input pl-9" maxlength="100">
        </label>
        <label><span class="sr-only">Catégorie</span>
            <select wire:model.live="category" class="field-input">
                <option value="">Toutes les catégories</option>
                @foreach (\App\Models\Book::CATEGORIES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
        </label>
        <label><span class="sr-only">Statut</span>
            <select wire:model.live="status" class="field-input">
                <option value="">Tous les statuts</option>
                @foreach ([\App\Enums\ContentStatus::Draft, \App\Enums\ContentStatus::Scheduled, \App\Enums\ContentStatus::Published, \App\Enums\ContentStatus::Archived] as $s)<option value="{{ $s->value }}">{{ $s->label() }}</option>@endforeach
            </select>
        </label>
    </div>

    @if ($books->isEmpty())
        <x-empty-state title="Aucun livre" icon="book">Ajoutez le premier ouvrage de la bibliothèque.
            <x-slot:action><button type="button" wire:click="create" class="btn-primary">Ajouter un livre</button></x-slot:action>
        </x-empty-state>
    @else
        <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" role="list">
            @foreach ($books as $b)
                <li class="card flex gap-4 p-4" wire:key="b-{{ $b->id }}">
                    <button type="button" wire:click="preview({{ $b->id }})" class="w-20 shrink-0 self-start overflow-hidden rounded-md bg-surface-2 shadow" aria-label="Aperçu de {{ $b->title }}">
                        @if ($b->cover_path)
                            <img src="{{ $b->coverUrl() }}" srcset="{{ $b->coverSrcset() }}" sizes="80px" alt="" class="aspect-[2/3] w-full object-cover" loading="lazy">
                        @else
                            <span class="flex aspect-[2/3] items-center justify-center text-muted"><x-icon name="book" /></span>
                        @endif
                    </button>
                    <div class="flex min-w-0 flex-1 flex-col">
                        <div class="mb-1 flex flex-wrap items-center gap-2">
                            <x-status-badge :status="$b->status" />
                            @if ($b->status === \App\Enums\ContentStatus::Scheduled)<span class="text-xs text-muted">le {{ $b->scheduled_at?->setTimezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</span>@endif
                        </div>
                        <h2 class="line-clamp-2 font-serif font-bold">{{ $b->title }}</h2>
                        <p class="truncate text-sm text-muted">{{ $b->author }} · {{ $b->categoryLabel() }}</p>
                        <div class="mt-auto flex flex-wrap gap-1 pt-2">
                            <button type="button" wire:click="edit({{ $b->id }})" class="btn-ghost px-3"><x-icon name="edit" class="size-4" /> Modifier</button>
                            @if ($b->isPubliclyVisible())
                                <a href="{{ $b->publicUrl() }}" target="_blank" rel="noopener" class="btn-ghost min-w-11 px-2" aria-label="Voir la fiche publique"><x-icon name="eye" class="size-4" /></a>
                            @endif
                            <x-confirm-action method="delete" :arg="$b->id" label="Supprimer" title="Supprimer « {{ \Illuminate\Support\Str::limit($b->title, 60) }} » ?" confirm="Supprimer" button-class="btn-ghost px-3 text-danger">
                                Le livre, son ebook et ses extraits seront supprimés définitivement.
                            </x-confirm-action>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $books->links() }}</div>
    @endif

    {{-- Aperçu --}}
    <x-modal :show="$previewed !== null" :title="$previewed?->title ?? ''">
        @if ($previewed)
            <p class="text-sm text-muted">{{ $previewed->author }} · {{ $previewed->categoryLabel() }} · {{ $previewed->releaseLabel() }}</p>
            <p class="mt-3">{{ $previewed->excerpt(500) }}</p>
        @endif
        <x-slot:footer>
            @if ($previewed)<button type="button" wire:click="edit({{ $previewed->id }})" class="btn-primary">Modifier</button>@endif
        </x-slot:footer>
    </x-modal>

    {{-- Éditeur --}}
    <x-modal :show="$editing" :title="$bookId ? 'Modifier le livre' : 'Nouveau livre'" max-width="max-w-4xl">
        @if ($editing)
            <div x-data="unsavedGuard" x-on:input="markDirty">
                <x-tabs :tabs="\App\Livewire\Dashboard\Books::TABS" :current="$tab" />
                @if ($errors->any())
                    <p class="mb-4 rounded-lg border border-danger/40 bg-danger/10 p-3 text-sm text-danger" role="alert">Des champs sont à corriger : {{ collect($errors->keys())->map(fn ($k) => explode('.', $k)[0])->unique()->implode(', ') }}.</p>
                @endif

                <div @class(['hidden' => $tab !== 'identite'])>
                    <x-editor.field model="form.title" label="Titre" maxlength="300" required />
                    <x-editor.field model="form.slug" label="Identifiant d'URL (facultatif)" help="Modifiable ; généré depuis le titre si vide." maxlength="300" />
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <x-editor.field model="form.author" label="Auteur (obligatoire)" maxlength="200" required />
                        <x-editor.field model="form.publisher" label="Éditeur" maxlength="200" />
                        <x-editor.field model="form.year" label="Année de parution" type="number" min="1400" />
                        <div class="mb-4">
                            <label for="f-month" class="field-label">Mois de parution</label>
                            <select id="f-month" wire:model="form.month" class="field-input">
                                <option value="">—</option>
                                @foreach (range(1, 12) as $m)<option value="{{ $m }}">{{ \Carbon\CarbonImmutable::create(2000, $m, 1)->translatedFormat('F') }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="f-cat" class="field-label">Catégorie</label>
                            <select id="f-cat" wire:model="form.category" class="field-input">
                                @foreach (\App\Models\Book::CATEGORIES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                            </select>
                            @error('category')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="mb-4">
                            <label for="f-diff" class="field-label">Difficulté</label>
                            <select id="f-diff" wire:model="form.difficulty" class="field-input">
                                <option value="">—</option>
                                @foreach (\App\Models\Book::DIFFICULTIES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                            </select>
                        </div>
                        <x-editor.field model="form.pages" label="Pages" type="number" min="1" />
                        <x-editor.field model="form.doi" label="DOI" placeholder="10.xxxx/…" />
                    </div>
                    <div class="mb-4">
                        <label for="f-cover" class="field-label">Couverture (JPEG, PNG, WebP, GIF, AVIF — 5 Mo)</label>
                        <input id="f-cover" type="file" wire:model="cover" accept="image/jpeg,image/png,image/webp,image/gif,image/avif" class="field-input py-2">
                        <p wire:loading wire:target="cover" class="field-help">Envoi en cours…</p>
                        @error('file')<p class="field-error">{{ $message }}</p>@enderror
                        @if ($current?->cover_path)<p class="field-help">Une couverture est déjà enregistrée ; un nouvel envoi la remplace.</p>@endif
                    </div>
                    <x-editor.field model="form.cover_alt" label="Texte alternatif de la couverture" maxlength="300" />
                </div>

                <div @class(['hidden' => $tab !== 'avis'])>
                    <x-editor.field model="form.summary" label="Résumé" :textarea="true" :rows="4" maxlength="5000" />
                    <x-editor.field model="form.review" label="Avis personnel (obligatoire)" :textarea="true" :rows="6" maxlength="20000" required />
                    <x-editor.field model="form.quotes" label="Citations favorites" :textarea="true" :rows="5" help="Séparez les citations par une ligne vide." />
                    <x-editor.field model="form.external_url" label="Ressource externe (lien de consultation)" type="url" placeholder="https://" />
                    <div class="mb-4">
                        <p class="field-label">Ouvrages similaires</p>
                        <ul class="mb-2 flex flex-wrap gap-2">
                            @foreach ($relatedItems as $r)
                                <li class="badge min-h-9 gap-1 bg-surface-2 pl-3 text-sm">{{ $r->title }} <button type="button" wire:click="removeRelated({{ $r->id }})" class="btn-ghost min-h-9 min-w-9 px-1" aria-label="Retirer {{ $r->title }}"><x-icon name="close" class="size-4" /></button></li>
                            @endforeach
                        </ul>
                        <label for="f-rel" class="sr-only">Rechercher un livre de la plateforme</label>
                        <input id="f-rel" type="search" wire:model.live.debounce.300ms="relatedSearch" placeholder="Rechercher un livre de la plateforme…" class="field-input" maxlength="100">
                        @if ($relatedResults->isNotEmpty())
                            <ul class="card mt-1 divide-y divide-line">
                                @foreach ($relatedResults as $r)<li><button type="button" wire:click="addRelated({{ $r->id }})" class="flex min-h-11 w-full items-center px-3 text-left text-sm hover:bg-surface-2">{{ $r->title }}</button></li>@endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                <div @class(['hidden' => $tab !== 'vente'])>
                    <p class="mb-4 text-sm text-muted">Prix en euros. Un bouton d'achat n'apparaît sur la fiche que si son lien est renseigné.</p>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <x-editor.field model="form.isbn_print" label="ISBN papier" maxlength="20" />
                        <x-editor.field model="form.isbn_ebook" label="ISBN ebook" maxlength="20" />
                        <x-editor.field model="form.price_print" label="Prix papier (€)" inputmode="decimal" placeholder="19,90" />
                        <x-editor.field model="form.price_ebook" label="Prix ebook (€)" inputmode="decimal" placeholder="9,99" />
                        <x-editor.field model="form.buy_print_url" label="Lien d'achat papier" type="url" placeholder="https://" />
                        <x-editor.field model="form.buy_ebook_url" label="Lien d'achat ebook" type="url" placeholder="https://" />
                    </div>
                </div>

                <div @class(['hidden' => $tab !== 'ebook'])>
                    <div class="mb-4" x-data="pdfInspector">
                        <label for="f-ebook" class="field-label">PDF de l'ebook (50 Mo, stocké hors de l'espace public)</label>
                        <input id="f-ebook" type="file" wire:model="ebook" accept="application/pdf" x-on:change="inspect" class="field-input py-2">
                        <p class="field-help" x-text="progress"></p>
                        <p wire:loading wire:target="ebook" class="field-help">Envoi en cours…</p>
                        @if ($current?->ebook_path)<p class="field-help">Un PDF est déjà enregistré ({{ \App\Support\Format::fileSize((int) $current->ebook_size) }}).</p>@endif
                    </div>
                    <x-editor.field model="form.ebook_description" label="Description de l'ebook" :textarea="true" :rows="4" maxlength="5000" />
                    <fieldset class="mb-4">
                        <legend class="field-label">Composition de l'ouvrage</legend>
                        <ol class="space-y-2">
                            @foreach ($form['composition'] as $i => $row)
                                <li class="flex gap-2" wire:key="comp-{{ $i }}">
                                    <label class="sr-only" for="comp-{{ $i }}">Titre du chapitre {{ $i + 1 }}</label>
                                    <input id="comp-{{ $i }}" wire:model="form.composition.{{ $i }}.title" class="field-input flex-1" maxlength="300" placeholder="Chapitre">
                                    <label class="sr-only" for="compp-{{ $i }}">Page</label>
                                    <input id="compp-{{ $i }}" wire:model="form.composition.{{ $i }}.page" type="number" min="1" class="field-input w-24" placeholder="p.">
                                    <button type="button" wire:click="removeComposition({{ $i }})" class="btn-ghost min-w-11 px-2" aria-label="Retirer le chapitre {{ $i + 1 }}"><x-icon name="trash" class="size-4" /></button>
                                </li>
                            @endforeach
                        </ol>
                        <button type="button" wire:click="addComposition" class="btn-secondary mt-2"><x-icon name="plus" class="size-4" /> Ajouter un chapitre</button>
                    </fieldset>
                    <fieldset class="mb-4">
                        <legend class="field-label">Extraits de chapitres téléchargeables (PDF, 20 Mo)</legend>
                        <ul class="mb-2 space-y-1 text-sm">
                            @foreach ($form['excerpts'] as $i => $ex)
                                <li class="flex items-center justify-between gap-2 rounded-lg bg-surface-2 px-3" wire:key="ex-{{ $i }}">{{ $ex['label'] }} <button type="button" wire:click="removeExcerpt({{ $i }})" class="btn-ghost min-w-11 px-2" aria-label="Retirer {{ $ex['label'] }}"><x-icon name="trash" class="size-4" /></button></li>
                            @endforeach
                        </ul>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label for="f-exl" class="sr-only">Libellé de l'extrait</label>
                            <input id="f-exl" wire:model="excerptLabel" class="field-input" maxlength="200" placeholder="Libellé (ex. Chapitre 1)">
                            <label for="f-ex" class="sr-only">Fichier PDF de l'extrait</label>
                            <input id="f-ex" type="file" wire:model="excerpt" accept="application/pdf" class="field-input py-2">
                        </div>
                        <p class="field-help">L'extrait est ajouté à l'enregistrement.</p>
                    </fieldset>
                </div>

                <div @class(['hidden' => $tab !== 'fiche'])>
                    <x-editor.field model="form.description" label="Description" :textarea="true" :rows="8" maxlength="20000" />
                    <x-editor.field model="form.author_bio" label="Biographie de l'auteur" :textarea="true" :rows="5" maxlength="5000" />
                    <x-editor.field model="form.seo_title" label="Titre pour les moteurs de recherche" maxlength="200" />
                    <x-editor.field model="form.seo_description" label="Description pour les moteurs de recherche" :textarea="true" :rows="2" maxlength="320" />
                </div>

                <div @class(['hidden' => $tab !== 'publication'])>
                    <p class="mb-4 text-sm">Statut actuel : <strong>{{ $current?->status->label() ?? 'non enregistré' }}</strong></p>
                    <div class="grid gap-x-4 sm:grid-cols-2">
                        <x-editor.field model="form.scheduled_date" label="Date de publication programmée" type="date" />
                        <x-editor.field model="form.scheduled_time" label="Heure" type="time" />
                    </div>
                    @error('scheduled_at')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        @endif
        <x-slot:footer>
            <button type="button" wire:click="save('draft')" class="btn-secondary" wire:loading.attr="disabled" wire:target="cover,ebook,excerpt,save">Enregistrer le brouillon</button>
            <button type="button" wire:click="save('schedule')" class="btn-secondary" wire:loading.attr="disabled" wire:target="cover,ebook,excerpt,save">Programmer</button>
            <button type="button" wire:click="save('publish')" class="btn-primary" wire:loading.attr="disabled" wire:target="cover,ebook,excerpt,save">Publier</button>
        </x-slot:footer>
    </x-modal>
</div>
