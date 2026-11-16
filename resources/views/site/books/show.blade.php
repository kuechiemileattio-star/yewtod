{{-- Fiche livre 100 % en français (cahier § 2.6, décision 5, bug n° 13). --}}
@php
    $euros = fn ($p) => \App\Models\Book::euros($p);
    $safe = fn ($u) => \App\Rules\HttpUrl::isSafe($u);
    $tabs = ['Description', 'Livre électronique', 'Auteur'];
@endphp
<x-layouts.public :title="$book->seo_title ?: $book->title" :description="$book->seo_description ?: $book->excerpt(160)" :canonical="$book->publicUrl()"
    :image="$book->coverUrl()" og-type="book" :json-ld="$book->jsonLd()">
    <article class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <nav aria-label="Fil d'Ariane" class="text-sm text-muted"><a class="hover:underline" href="{{ url('/books') }}">Livres</a> / <a class="hover:underline" href="{{ url('/books').'?categorie='.$book->category }}">{{ $book->categoryLabel() }}</a></nav>
        <header class="mt-4 max-w-4xl">
            <h1 class="text-4xl font-bold leading-tight sm:text-5xl">{{ $book->title }}</h1>
            <p class="mt-3 text-lg"><span class="text-muted">par</span> <strong>{{ $book->author }}</strong></p>
        </header>

        <div class="mt-10 grid grid-cols-1 gap-10 lg:grid-cols-[18rem_1fr]">
            {{-- Colonne « Ce livre » --}}
            <aside aria-labelledby="ce-livre" class="lg:sticky lg:top-24 lg:self-start">
                <div class="mx-auto max-w-[16rem] overflow-hidden rounded-lg shadow-2xl lg:max-w-none"><x-site.book-cover :book="$book" :eager="true" sizes="(min-width: 1024px) 288px, 256px" /></div>
                <section class="card mt-6 p-5">
                    <h2 id="ce-livre" class="text-xs font-bold uppercase tracking-wider text-muted">Ce livre</h2>
                    <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
                        <dt class="text-muted">Catégorie</dt><dd>{{ $book->categoryLabel() }}</dd>
                        @if ($book->difficulty)<dt class="text-muted">Niveau</dt><dd>{{ \App\Models\Book::DIFFICULTIES[$book->difficulty] ?? '' }}</dd>@endif
                        @if ($book->pages)<dt class="text-muted">Pages</dt><dd>{{ $book->pages }}</dd>@endif
                        @if ($book->releaseLabel())<dt class="text-muted">Parution</dt><dd>{{ ucfirst($book->releaseLabel()) }}</dd>@endif
                        @if ($book->publisher)<dt class="text-muted">Éditeur</dt><dd>{{ $book->publisher }}</dd>@endif
                        @if ($book->doi)<dt class="text-muted">DOI</dt><dd class="break-all"><a class="link" href="https://doi.org/{{ $book->doi }}" target="_blank" rel="noopener noreferrer">{{ $book->doi }}</a></dd>@endif
                        @if ($book->isbn_print)<dt class="text-muted">ISBN papier</dt><dd>{{ $book->isbn_print }}</dd>@endif
                        @if ($book->isbn_ebook)<dt class="text-muted">ISBN numérique</dt><dd>{{ $book->isbn_ebook }}</dd>@endif
                    </dl>
                    @foreach ([['Version papier', $book->price_print, $book->buy_print_url], ['Version numérique', $book->price_ebook, $book->buy_ebook_url]] as [$label, $price, $link])
                        @if ($price !== null || $safe($link))
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-line pt-4">
                                <p class="text-sm"><span class="block text-muted">{{ $label }}</span><strong class="text-lg">{{ $euros($price) ?? 'Prix non communiqué' }}</strong></p>
                                {{-- Bouton d'achat affiché seulement si un lien valide existe (bug n° 13). --}}
                                @if ($safe($link))<a href="{{ $link }}" target="_blank" rel="noopener noreferrer" class="btn-primary">Acheter <x-icon name="external" class="size-4" /></a>@endif
                            </div>
                        @endif
                    @endforeach
                </section>
            </aside>

            <div class="min-w-0">
                {{-- Onglets : sans JavaScript, les trois panneaux s'affichent à la suite. --}}
                <div x-data="sectionToc">
                    <div class="-mx-4 overflow-x-auto border-b border-line px-4 sm:mx-0 sm:px-0" role="tablist" aria-label="Sections de la fiche">
                        <div class="flex min-w-max gap-1">
                            @foreach ($tabs as $i => $label)
                                <a href="#onglet-{{ $i }}" role="tab" data-index="{{ $i }}" x-on:click="select" aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                                   class="toc-tab inline-flex min-h-11 items-center border-b-2 border-transparent px-4 text-sm font-semibold text-muted hover:text-ink aria-selected:border-accent aria-selected:text-ink">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>
                    <section id="onglet-0" class="toc-panel pt-6" data-index="0" role="tabpanel" tabindex="-1" aria-label="Description">
                        @if ($book->summary)<p class="font-serif text-xl leading-relaxed">{{ $book->summary }}</p>@endif
                        @if ($book->description)<div class="mt-4 whitespace-pre-line">{{ $book->description }}</div>@endif
                        @if ($book->excerpts)
                            <h2 class="mt-8 text-xl font-bold">Extraits à télécharger</h2>
                            <ul class="mt-3 flex flex-wrap gap-2">
                                @foreach ($book->excerpts as $i => $ex)
                                    <li><a href="{{ route('books.excerpt', [$book->slug, $i]) }}" class="btn-secondary"><x-icon name="download" class="size-4" /> {{ $ex['label'] }} @if (! empty($ex['size']))<span class="text-xs text-muted">{{ \App\Support\Format::fileSize((int) $ex['size']) }}</span>@endif</a></li>
                                @endforeach
                            </ul>
                        @endif
                        @if ($book->composition)
                            <h2 class="mt-8 text-xl font-bold">Composition de l'ouvrage</h2>
                            <ol class="mt-3 divide-y divide-line border-y border-line">
                                @foreach ($book->composition as $row)
                                    <li class="flex justify-between gap-4 py-2"><span>{{ $row['title'] }}</span>@if (! empty($row['page']))<span class="shrink-0 text-sm text-muted">p. {{ $row['page'] }}</span>@endif</li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                    <section id="onglet-1" class="toc-panel pt-6" data-index="1" role="tabpanel" tabindex="-1" aria-label="Livre électronique">
                        @if ($book->ebook_description)<p class="whitespace-pre-line">{{ $book->ebook_description }}</p>@endif
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($book->ebook_path)<a href="{{ route('books.ebook', $book->slug) }}" target="_blank" rel="noopener" class="btn-primary"><x-icon name="book" class="size-4" /> Lire le PDF</a>@endif
                            @if ($safe($book->buy_ebook_url))<a href="{{ $book->buy_ebook_url }}" target="_blank" rel="noopener noreferrer" class="btn-secondary">Acheter la version numérique @if ($book->price_ebook !== null)({{ $euros($book->price_ebook) }})@endif</a>@endif
                        </div>
                        @if (! $book->ebook_path && ! $book->ebook_description && ! $safe($book->buy_ebook_url))
                            <p class="text-muted">Aucune version électronique n'est proposée pour ce livre.</p>
                        @endif
                    </section>
                    <section id="onglet-2" class="toc-panel pt-6" data-index="2" role="tabpanel" tabindex="-1" aria-label="Auteur">
                        <h2 class="text-2xl font-bold">{{ $book->author }}</h2>
                        <p class="mt-3 whitespace-pre-line">{{ $book->author_bio ?: 'Biographie à venir.' }}</p>
                    </section>
                </div>

                <section class="mt-12 rounded-2xl bg-surface-2 p-6 sm:p-8" aria-labelledby="avis-yewtod">
                    <h2 id="avis-yewtod" class="text-xs font-bold uppercase tracking-[0.2em] text-accent-ink dark:text-accent">L'avis de Yewtod</h2>
                    <div class="mt-3 whitespace-pre-line font-serif text-lg leading-relaxed">{{ $book->review }}</div>
                </section>

                @if ($book->quotes)
                    <section class="mt-10" aria-labelledby="citations">
                        <h2 id="citations" class="text-2xl font-bold">Citations favorites</h2>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            @foreach ($book->quotes as $quote)
                                <blockquote class="card border-l-4 border-l-accent p-5 font-serif italic">« {{ $quote }} »</blockquote>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($safe($book->external_url))
                    <p class="mt-8"><a class="link inline-flex min-h-11 items-center gap-1" href="{{ $book->external_url }}" target="_blank" rel="noopener noreferrer">Ressource externe <x-icon name="external" class="size-4" /></a></p>
                @endif

                <div x-data="copyText" data-text="{{ $book->citation() }}" class="mt-8">
                    <button type="button" x-on:click="copy" class="btn-secondary"><x-icon name="quote" class="size-4" /> <span x-show="!copied">Copier la référence</span><span x-show="copied" x-cloak>Référence copiée</span></button>
                </div>

                @includeIf('partials.public.reviews', ['reviewable' => $book])
            </div>
        </div>
    </article>

    @if ($alsoRead->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8" aria-labelledby="aussi-livres">
            <x-site.section-title eyebrow="Bibliothèque" title="À lire aussi" id="aussi-livres" :link="url('/books')" link-label="Tous les livres" />
            <ul class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                @foreach ($alsoRead as $b)
                    <li class="group relative">
                        <div class="overflow-hidden rounded-lg shadow-md"><x-site.book-cover :book="$b" sizes="(min-width: 640px) 25vw, 50vw" /></div>
                        <h3 class="mt-3 font-serif font-bold leading-snug"><a href="{{ $b->publicUrl() }}" class="after:absolute after:inset-0">{{ $b->title }}</a></h3>
                        <p class="text-sm text-muted">{{ $b->author }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-layouts.public>
