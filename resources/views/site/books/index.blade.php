{{-- Bibliothèque (cahier § 2.6) : recherche, filtres, tri, mise en page magazine. --}}
@php($list = $books->getCollection())
@php($magazine = ! $filtered && $books->currentPage() === 1 && $filters['sort'] === 'recent' && $list->count() >= 4)
<x-layouts.public title="Livres" description="La bibliothèque commentée de Yewtod SS : des ouvrages de sciences sociales, d'économie, d'histoire et de philosophie, lus et recommandés." :noindex="$filtered || $books->currentPage() > 1" :canonical="url('/books')">
    <section class="border-b border-line bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-accent-ink dark:text-accent">Bibliothèque</p>
            <h1 class="mt-2 text-4xl font-bold sm:text-5xl">Livres</h1>
            <p class="mt-3 max-w-2xl text-muted">Des ouvrages lus, commentés et recommandés pour aller plus loin. {{ $total }} livre{{ $total > 1 ? 's' : '' }} dans la bibliothèque.</p>
            <form method="GET" action="{{ url('/books') }}" role="search" x-data="autoSubmit" x-on:change="submit" class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_1fr_auto]">
                <div>
                    <label for="bq" class="sr-only">Rechercher par titre ou auteur</label>
                    <input id="bq" name="q" type="search" value="{{ $filters['q'] }}" maxlength="100" placeholder="Rechercher par titre ou auteur…" class="field-input">
                </div>
                <div><label for="bcat" class="sr-only">Catégorie</label>
                    <select id="bcat" name="categorie" class="field-input">
                        <option value="">Toutes les catégories</option>
                        @foreach (\App\Models\Book::CATEGORIES as $key => $label)<option value="{{ $key }}" @selected($filters['category'] === $key)>{{ $label }} ({{ $categoryCounts[$key] ?? 0 }})</option>@endforeach
                    </select></div>
                <div><label for="baut" class="sr-only">Auteur</label>
                    <select id="baut" name="auteur" class="field-input">
                        <option value="">Tous les auteurs</option>
                        @foreach ($authors as $a)<option value="{{ $a }}" @selected($filters['author'] === $a)>{{ $a }}</option>@endforeach
                    </select></div>
                <div><label for="byear" class="sr-only">Année</label>
                    <select id="byear" name="annee" class="field-input">
                        <option value="">Toutes les années</option>
                        @foreach ($years as $y)<option value="{{ $y }}" @selected($filters['year'] === $y)>{{ $y }}</option>@endforeach
                    </select></div>
                <div><label for="btri" class="sr-only">Trier</label>
                    <select id="btri" name="tri" class="field-input">
                        @foreach ($sorts as $key => $label)<option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>@endforeach
                    </select></div>
                <div class="flex gap-2"><button class="btn-primary flex-1">Filtrer</button>@if ($filtered)<a href="{{ url('/books') }}" class="btn-secondary">Effacer</a>@endif</div>
            </form>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($list->isEmpty())
            <x-empty-state icon="book" :title="$filtered ? 'Aucun livre ne correspond' : 'La bibliothèque se remplit bientôt'">
                @if ($filtered)Essayez une autre recherche.@endif
            </x-empty-state>
        @else
            @if ($magazine)
                {{-- Mise en page magazine : un livre vedette, trois en colonne, puis la grille. --}}
                @php($lead = $list->first())
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-[1.4fr_1fr]">
                    <article class="relative grid grid-cols-1 gap-6 card p-6 sm:grid-cols-[12rem_1fr]">
                        <div class="mx-auto w-40 overflow-hidden rounded-lg shadow-xl sm:w-full"><x-site.book-cover :book="$lead" :eager="true" sizes="200px" /></div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-accent-ink dark:text-accent">À la une · {{ $lead->categoryLabel() }}</p>
                            <h2 class="mt-2 text-3xl font-bold"><a href="{{ $lead->publicUrl() }}" class="after:absolute after:inset-0">{{ $lead->title }}</a></h2>
                            <p class="mt-1 font-semibold text-muted">{{ $lead->author }}@if ($lead->releaseLabel()) · {{ $lead->releaseLabel() }}@endif</p>
                            <p class="mt-4">{{ \Illuminate\Support\Str::limit($lead->review, 320) }}</p>
                        </div>
                    </article>
                    <ul class="grid gap-4">
                        @foreach ($list->slice(1, 3) as $b)
                            <li class="relative flex gap-4 card p-4">
                                <div class="w-16 shrink-0 overflow-hidden rounded shadow"><x-site.book-cover :book="$b" sizes="64px" /></div>
                                <div class="min-w-0">
                                    <h3 class="font-serif font-bold leading-snug"><a href="{{ $b->publicUrl() }}" class="after:absolute after:inset-0">{{ $b->title }}</a></h3>
                                    <p class="text-sm text-muted">{{ $b->author }}</p>
                                    <p class="mt-1 line-clamp-2 text-sm">{{ $b->excerpt(140) }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @php($rest = $list->slice(4))
            @else
                @php($rest = $list)
            @endif
            @if ($rest->isNotEmpty())
                <ul class="mt-10 grid grid-cols-2 gap-x-5 gap-y-8 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    @foreach ($rest as $b)
                        <li class="group relative">
                            <div class="overflow-hidden rounded-lg shadow-md transition group-hover:-translate-y-1 group-hover:shadow-xl"><x-site.book-cover :book="$b" sizes="(min-width: 1280px) 18vw, (min-width: 640px) 30vw, 45vw" /></div>
                            <h3 class="mt-3 font-serif font-bold leading-snug"><a href="{{ $b->publicUrl() }}" class="after:absolute after:inset-0">{{ $b->title }}</a></h3>
                            <p class="text-sm text-muted">{{ $b->author }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-10">{{ $books->onEachSide(1)->links('vendor.pagination.tailwind') }}</div>
        @endif
    </div>
</x-layouts.public>
