{{-- Bibliothèque (cahier § 2.6 ; design de référence D78) : recherche, filtres, tri, mise en page magazine. --}}
@php($list = $books->getCollection())
@php($magazine = ! $filtered && $books->currentPage() === 1 && $filters['sort'] === 'recent' && $list->count() >= 2)
@php($tag = 'text-xs font-bold uppercase tracking-[0.02em] text-accent-2-ink')
<x-layouts.public title="Livres" description="La bibliothèque commentée de Yewtod SS : des ouvrages de sciences sociales, d'économie, d'histoire et de philosophie, lus et recommandés." :noindex="$filtered || $books->currentPage() > 1" :canonical="url('/books')">
    <div class="mx-auto max-w-[1180px] px-6 pb-[110px] pt-12 sm:pt-[72px]">
        <p class="kicker mb-2">Bibliothèque</p>
        <h1 class="mb-3 text-[clamp(2.1rem,4.5vw,3rem)]">Livres</h1>
        <p class="mb-10 max-w-[640px] text-base text-muted">Des ouvrages lus, commentés et recommandés pour aller plus loin : un avis personnel, des citations favorites et un niveau de difficulté pour chacun. {{ $total }} livre{{ $total > 1 ? 's' : '' }} dans la bibliothèque.</p>

        <form method="GET" action="{{ url('/books') }}" role="search" x-data="autoSubmit" x-on:change="submit"
              class="mb-11 grid grid-cols-2 gap-3 border-b border-line pb-6 sm:flex sm:flex-wrap sm:items-center">
            <label for="bq" class="col-span-2 flex min-h-11 min-w-[200px] flex-[1_1_240px] items-center gap-2 border border-line bg-page px-3">
                <x-icon name="search" class="size-4 shrink-0 text-muted" />
                <span class="sr-only">Rechercher par titre ou auteur</span>
                <input id="bq" name="q" type="search" value="{{ $filters['q'] }}" maxlength="100" placeholder="Rechercher un titre ou un auteur" class="w-full border-0 bg-transparent p-0 text-[13.5px] focus:ring-0">
            </label>
            <label for="bcat" class="sr-only">Catégorie</label>
            <select id="bcat" name="categorie" class="book-select">
                <option value="">Toutes les catégories</option>
                @foreach (\App\Models\Book::CATEGORIES as $key => $label)<option value="{{ $key }}" @selected($filters['category'] === $key)>{{ $label }} ({{ $categoryCounts[$key] ?? 0 }})</option>@endforeach
            </select>
            <label for="baut" class="sr-only">Auteur</label>
            <select id="baut" name="auteur" class="book-select">
                <option value="">Tous les auteurs</option>
                @foreach ($authors as $a)<option value="{{ $a }}" @selected($filters['author'] === $a)>{{ $a }}</option>@endforeach
            </select>
            <label for="byear" class="sr-only">Année</label>
            <select id="byear" name="annee" class="book-select">
                <option value="">Toutes les années</option>
                @foreach ($years as $y)<option value="{{ $y }}" @selected($filters['year'] === $y)>{{ $y }}</option>@endforeach
            </select>
            <label for="btri" class="sr-only">Trier</label>
            <select id="btri" name="tri" class="book-select">
                @foreach ($sorts as $key => $label)<option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>@endforeach
            </select>
            <div class="col-span-2 flex gap-2 sm:col-span-1">
                <button class="btn-primary flex-1 sm:flex-none">Filtrer</button>
                @if ($filtered)<a href="{{ url('/books') }}" class="btn-secondary">Effacer</a>@endif
            </div>
        </form>

        @if ($list->isEmpty())
            <x-empty-state icon="book" :title="$filtered ? 'Aucun livre ne correspond' : 'La bibliothèque se remplit bientôt'">
                @if ($filtered)Essayez une autre recherche.@endif
            </x-empty-state>
        @else
            @if ($magazine)
                @php($lead = $list->first())
                @php($side = $list->slice(1, 3))
                <div class="mb-14 grid grid-cols-1 gap-8 border-b border-line pb-12 lg:grid-cols-[1.6fr_1px_1fr]" x-data="reveal" x-bind:class="revealClass">
                    <article class="group relative">
                        <div class="book-frame h-[clamp(260px,38vw,480px)]"><x-site.book-cover :book="$lead" :eager="true" :fill="true" sizes="(min-width: 1024px) 680px, 100vw" /></div>
                        <div class="pt-[18px]">
                            <p class="{{ $tag }}">{{ $lead->categoryLabel() }}</p>
                            <h2 class="my-2.5 font-sans text-[clamp(2rem,4vw,3.25rem)] font-extrabold leading-[1.05]"><a href="{{ $lead->publicUrl() }}" class="after:absolute after:inset-0 group-hover:text-accent-ink">{{ $lead->title }}</a></h2>
                            <p class="text-base">{{ $lead->author }}</p>
                            @if ($lead->releaseLabel())<p class="mt-1 text-[13px] text-muted">{{ $lead->releaseLabel() }}</p>@endif
                        </div>
                    </article>
                    @if ($side->isNotEmpty())
                        <div class="hidden bg-line lg:block" aria-hidden="true"></div>
                        <ul>
                            @foreach ($side as $b)
                                <li class="group relative flex items-start gap-4 border-line py-[18px] [&:not(:first-child)]:border-t">
                                    <div class="book-frame h-[100px] w-[74px] shrink-0"><x-site.book-cover :book="$b" :fill="true" sizes="74px" /></div>
                                    <div class="min-w-0">
                                        <p class="{{ $tag }}">{{ $b->categoryLabel() }}</p>
                                        <h3 class="mb-1 mt-1 font-sans text-lg font-extrabold leading-tight"><a href="{{ $b->publicUrl() }}" class="after:absolute after:inset-0 group-hover:text-accent-ink">{{ $b->title }}</a></h3>
                                        <p class="text-[13.5px]">{{ $b->author }}</p>
                                        @if ($b->releaseLabel())<p class="mt-0.5 text-xs text-muted">{{ $b->releaseLabel() }}</p>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @php($rest = $list->slice(4))
            @else
                @php($rest = $list)
            @endif
            @if ($rest->isNotEmpty())
                <ul class="books-grid stagger">
                    @foreach ($rest as $b)
                        <li class="group relative">
                            <p class="{{ $tag }}">{{ $b->categoryLabel() }}</p>
                            <div class="book-frame mt-2 h-[220px]"><x-site.book-cover :book="$b" :fill="true" sizes="(min-width: 1024px) 260px, (min-width: 640px) 45vw, 100vw" /></div>
                            <h3 class="mb-1.5 mt-3.5 font-sans text-[19px] font-extrabold leading-tight"><a href="{{ $b->publicUrl() }}" class="after:absolute after:inset-0 group-hover:text-accent-ink">{{ $b->title }}</a></h3>
                            <p class="text-[13.5px]">{{ $b->author }}</p>
                            @if ($b->releaseLabel())<p class="mt-0.5 text-xs text-muted">{{ $b->releaseLabel() }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="mt-10">{{ $books->onEachSide(1)->links('vendor.pagination.tailwind') }}</div>
        @endif
    </div>
</x-layouts.public>
