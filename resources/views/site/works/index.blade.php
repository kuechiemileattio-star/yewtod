{{--
    Catalogue (cahier § 2.3, § 10 P1) : filtres cumulables reflétés dans l'URL
    (formulaire GET : partageable, bouton retour du navigateur), pagination.
    Sans JavaScript, le bouton « Appliquer » suffit ; avec, chaque changement
    soumet le formulaire (composant autoSubmit).
--}}
@php
    $title = count($filters['types']) === 1 ? \App\Enums\ContentType::from($filters['types'][0])->plural() : 'Publications';
    $pill = count($filters['types']) === 1 ? $filters['types'][0] : null;
    $keep = array_filter(['q' => $filters['q'] ?: null, 'tri' => $filters['tri'] !== 'recent' ? $filters['tri'] : null]);
@endphp
<x-layouts.public :title="$title" :noindex="$items->currentPage() > 1 || $hasFilters && ! $pill"
    :canonical="$pill ? url('/works').'?categorie='.$pill : url('/works')"
    description="Toutes les publications de Yewtod SS : articles, rapports, études, notes de recherche, documentaires, expérimentations et visualisations de données.">
    <section class="border-b border-line bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-accent-ink dark:text-accent">Travaux</p>
            <h1 class="mt-2 text-4xl font-bold sm:text-5xl">{{ $title }}</h1>
            <p class="mt-3 text-muted">{{ trans_choice(':count publication en accès libre|:count publications en accès libre', $total, ['count' => number_format($total, 0, ',', ' ')]) }}</p>
            {{-- Pastilles de type avec compteur --}}
            <nav class="-mx-4 mt-6 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0" aria-label="Types de publications">
                <ul class="flex w-max gap-2 sm:w-auto sm:flex-wrap">
                    <li><a href="{{ url('/works').($keep ? '?'.http_build_query($keep) : '') }}" @if (! $pill && ! $filters['types']) aria-current="page" @endif
                           class="badge min-h-11 gap-2 border px-4 text-sm {{ ! $pill && ! $filters['types'] ? 'border-ink bg-ink text-page' : 'border-line bg-page hover:border-line-strong' }}">Toutes <span class="opacity-70">{{ $total }}</span></a></li>
                    @foreach (\App\Enums\ContentType::cases() as $type)
                        <li><a href="{{ url('/works').'?'.http_build_query(['categorie' => $type->value] + $keep) }}" @if ($pill === $type->value) aria-current="page" @endif
                               class="badge min-h-11 gap-2 border px-4 text-sm {{ $pill === $type->value ? 'border-ink bg-ink text-page' : 'border-line bg-page hover:border-line-strong' }}">{{ $type->plural() }} <span class="opacity-70">{{ $typeCounts[$type->value] }}</span></a></li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[17rem_1fr] lg:px-8" x-data="drawer">
        {{-- Filtres : colonne sur grand écran, tiroir sur mobile --}}
        <div class="lg:hidden">
            <button type="button" class="btn-secondary w-full" x-on:click="show" aria-controls="filtres">
                <x-icon name="list" class="size-4" /> Filtrer et trier @if ($hasFilters)<span class="badge bg-accent-ink text-white dark:bg-accent dark:text-on-accent">actifs</span>@endif
            </button>
        </div>
        <div class="fixed inset-0 z-40 bg-ink/40 lg:hidden" x-show="open" x-cloak x-on:click="close" aria-hidden="true"></div>
        <aside id="filtres" aria-label="Filtres"
               class="fixed inset-y-0 left-0 z-50 w-[min(22rem,90vw)] -translate-x-full overflow-y-auto bg-page p-5 shadow-xl transition-transform lg:static lg:z-auto lg:w-auto lg:translate-x-0 lg:overflow-visible lg:bg-transparent lg:p-0 lg:shadow-none"
               x-bind:class="open ? 'translate-x-0' : ''" x-on:keydown.escape="close">
            <div class="mb-4 flex items-center justify-between lg:hidden">
                <p class="font-serif text-xl font-bold">Filtres</p>
                <button type="button" class="btn-ghost min-w-11 px-2" x-on:click="close" aria-label="Fermer les filtres"><x-icon name="close" /></button>
            </div>
            <form method="GET" action="{{ url('/works') }}" x-data="autoSubmit" x-on:change="submit" class="space-y-6" role="search">
                <div>
                    <label for="q" class="field-label">Rechercher</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-3 size-5 text-muted" />
                        <input id="q" name="q" type="search" value="{{ $filters['q'] }}" maxlength="100" placeholder="Titre, résumé, mot-clé…" class="field-input pl-10">
                    </div>
                </div>
                <fieldset>
                    <legend class="field-label">Axe thématique</legend>
                    <ul class="space-y-1 text-sm">
                        <li><label class="flex min-h-11 items-center gap-2"><input type="radio" name="axe" value="" @checked(! $filters['axe']) class="text-accent-2-ink focus:ring-accent-2"> Tous les axes</label></li>
                        @foreach ($themes as $key => $label)
                            <li><label class="flex min-h-11 items-center gap-2"><input type="radio" name="axe" value="{{ $key }}" @checked($filters['axe'] === $key) class="text-accent-2-ink focus:ring-accent-2"> <span class="flex-1">{{ $label }}</span> <span class="text-xs text-muted">{{ $themeCounts[$key] }}</span></label></li>
                        @endforeach
                    </ul>
                </fieldset>
                <fieldset>
                    <legend class="field-label">Type</legend>
                    <ul class="space-y-1 text-sm">
                        @foreach (\App\Enums\ContentType::cases() as $type)
                            <li><label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="types[]" value="{{ $type->value }}" @checked(in_array($type->value, $filters['types'], true)) class="rounded text-accent-2-ink focus:ring-accent-2"> <span class="flex-1">{{ $type->plural() }}</span> <span class="text-xs text-muted">{{ $typeCounts[$type->value] }}</span></label></li>
                        @endforeach
                    </ul>
                </fieldset>
                @if ($series)
                    <div>
                        <label for="serie" class="field-label">Série documentaire</label>
                        <select id="serie" name="serie" class="field-input">
                            <option value="">Toutes les séries</option>
                            @foreach ($series as $id => $label)<option value="{{ $id }}" @selected($filters['serie'] === $id)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                @endif
                @if ($authors)
                    <div>
                        <label for="auteur" class="field-label">Auteur</label>
                        <select id="auteur" name="auteur" class="field-input">
                            <option value="">Tous les auteurs</option>
                            @foreach ($authors as $id => $label)<option value="{{ $id }}" @selected($filters['auteur'] === $id)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                @endif
                <fieldset>
                    <legend class="field-label">Période</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label for="du" class="text-xs text-muted">Du</label><input id="du" type="date" name="du" value="{{ $filters['du'] }}" class="field-input"></div>
                        <div><label for="au" class="text-xs text-muted">Au</label><input id="au" type="date" name="au" value="{{ $filters['au'] }}" class="field-input"></div>
                    </div>
                </fieldset>
                <div class="space-y-3 opacity-70">
                    <div><label for="pays" class="field-label">Pays</label><select id="pays" disabled class="field-input" aria-describedby="bientot"><option>Bientôt disponible</option></select></div>
                    <div><label for="langue" class="field-label">Langue</label><select id="langue" disabled class="field-input" aria-describedby="bientot"><option>Bientôt disponible</option></select></div>
                    <p id="bientot" class="field-help">Ces filtres seront alimentés prochainement.</p>
                </div>
                <div>
                    <label for="tri" class="field-label">Trier par</label>
                    <select id="tri" name="tri" class="field-input">
                        @foreach ($sorts as $key => $label)<option value="{{ $key }}" @selected($filters['tri'] === $key)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="btn-primary flex-1">Appliquer</button>
                    <a href="{{ url('/works') }}" class="btn-secondary flex-1">Réinitialiser</a>
                </div>
            </form>
        </aside>

        <section aria-labelledby="resultats" class="min-w-0">
            <h2 id="resultats" class="sr-only">Résultats</h2>
            <p class="mb-5 text-sm text-muted" role="status">{{ $items->total() }} résultat{{ $items->total() > 1 ? 's' : '' }} sur {{ $total }}</p>
            @if ($items->isEmpty())
                <x-empty-state icon="search" title="Aucune publication ne correspond">
                    Essayez d'élargir la période ou de retirer un filtre.
                    <x-slot:action><a href="{{ url('/works') }}" class="btn-secondary">Réinitialiser les filtres</a></x-slot:action>
                </x-empty-state>
            @else
                <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($items as $item)
                        <x-site.work-card :content="$item" heading-level="h3" />
                    @endforeach
                </div>
                <div class="mt-10">{{ $items->onEachSide(1)->links('vendor.pagination.tailwind') }}</div>
            @endif
        </section>
    </div>
</x-layouts.public>
