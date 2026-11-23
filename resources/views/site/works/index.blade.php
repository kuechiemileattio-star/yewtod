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
    <x-site.hero-band :title="$title" :text="trans_choice(':count analyse, rapport ou travail en accès libre — la bibliothèque de réflexion de Yewtod SS.|:count analyses, rapports et travaux en accès libre — la bibliothèque de réflexion de Yewtod SS.', $total, ['count' => number_format($total, 0, ',', ' ')])" />
    {{-- Pastilles de type avec compteur --}}
    <nav class="mx-auto max-w-[1280px] overflow-x-auto px-6 py-[18px]" aria-label="Types de publications">
        <ul class="flex w-max gap-2 sm:w-auto sm:flex-wrap">
            <li><a href="{{ url('/works').($keep ? '?'.http_build_query($keep) : '') }}" @if (! $pill && ! $filters['types']) aria-current="page" @endif
                   class="inline-flex min-h-10 items-center gap-1.5 rounded-full border px-3.5 text-[12.5px] font-semibold transition {{ ! $pill && ! $filters['types'] ? 'border-accent-2-ink bg-accent-2-ink text-on-accent-2' : 'border-line bg-card text-ink hover:border-accent' }}">Toutes <span class="text-[11px] font-normal">{{ $total }}</span></a></li>
            @foreach (\App\Enums\ContentType::cases() as $type)
                <li><a href="{{ url('/works').'?'.http_build_query(['categorie' => $type->value] + $keep) }}" @if ($pill === $type->value) aria-current="page" @endif
                       class="inline-flex min-h-10 items-center gap-1.5 rounded-full border px-3.5 text-[12.5px] font-semibold transition {{ $pill === $type->value ? 'border-accent-2-ink bg-accent-2-ink text-on-accent-2' : 'border-line bg-card text-ink hover:border-accent' }}">{{ $type->plural() }} <span class="text-[11px] font-normal">{{ $typeCounts[$type->value] }}</span></a></li>
            @endforeach
        </ul>
    </nav>

    <div class="mx-auto grid max-w-[1280px] grid-cols-1 items-start gap-8 px-6 pb-16 lg:grid-cols-[260px_minmax(0,1fr)]" x-data="drawer">
        {{-- Filtres : colonne sur grand écran, tiroir sur mobile --}}
        <div class="lg:hidden">
            <button type="button" class="btn-secondary w-full" x-on:click="show" aria-controls="filtres">
                <x-icon name="list" class="size-4" /> Filtrer et trier @if ($hasFilters)<span class="badge bg-accent-ink text-white dark:bg-accent dark:text-on-accent">actifs</span>@endif
            </button>
        </div>
        <div class="fixed inset-0 z-40 bg-ink/40 lg:hidden" x-show="open" x-cloak x-on:click="close" aria-hidden="true"></div>
        <aside id="filtres" aria-label="Filtres"
               class="fixed inset-y-0 left-0 z-50 w-[min(22rem,90vw)] -translate-x-full overflow-y-auto bg-page p-5 shadow-xl transition-transform lg:sticky lg:top-24 lg:z-auto lg:w-auto lg:translate-x-0 lg:overflow-visible lg:rounded-lg lg:border lg:border-line lg:bg-card lg:p-[18px] lg:shadow-none lg:backdrop-blur-sm"
               x-bind:class="open ? 'translate-x-0' : ''" x-on:keydown.escape="close">
            <div class="mb-4 flex items-center justify-between lg:hidden">
                <p class="font-serif text-xl">Filtres</p>
                <button type="button" class="btn-ghost min-w-11 px-2" x-on:click="close" aria-label="Fermer les filtres"><x-icon name="close" /></button>
            </div>
            <p class="mb-3.5 hidden font-mono text-[11px] font-bold uppercase tracking-[0.05em] lg:block">Filtrer</p>
            <form method="GET" action="{{ url('/works') }}" x-data="autoSubmit" x-on:change="submit" class="filters divide-y divide-line [&>*]:py-3.5 [&>*:first-child]:pt-0" role="search">
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
                <div class="space-y-3">
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
            <p class="mb-[22px] text-[13.5px] text-muted" role="status">{{ $items->total() }} résultat{{ $items->total() > 1 ? 's' : '' }} sur {{ $total }}</p>
            @if ($items->isEmpty())
                <x-empty-state icon="search" title="Aucune publication ne correspond">
                    Essayez d'élargir la période ou de retirer un filtre.
                    <x-slot:action><a href="{{ url('/works') }}" class="btn-secondary">Réinitialiser les filtres</a></x-slot:action>
                </x-empty-state>
            @else
                <div class="grid gap-7 md:grid-cols-2">
                    @foreach ($items as $item)
                        <x-site.work-card :content="$item" heading-level="h3" variant="full" />
                    @endforeach
                </div>
                <div class="mt-10">{{ $items->onEachSide(1)->links('vendor.pagination.tailwind') }}</div>
            @endif
        </section>
    </div>
</x-layouts.public>
