{{-- Accueil (cahier § 2.2) : rendu serveur, données calculées par HomeController. --}}
<x-layouts.public :description="$home['hero_text']" :json-ld="[
    '@context' => 'https://schema.org', '@type' => 'WebSite',
    'name' => app(\App\Services\SiteSettings::class)->siteName(), 'url' => url('/'),
    'potentialAction' => ['@type' => 'SearchAction', 'target' => url('/works').'?q={search_term_string}', 'query-input' => 'required name=search_term_string'],
]">
    {{-- Accroche + « À lire maintenant » --}}
    <section class="mx-auto grid max-w-7xl gap-10 px-4 pb-12 pt-10 sm:px-6 lg:grid-cols-[1.3fr_1fr] lg:items-center lg:px-8 lg:pt-16" aria-labelledby="accroche">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-accent-ink dark:text-accent">Média de recherche en sciences sociales</p>
            <h1 id="accroche" class="mt-3 text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">{{ $home['hero_title'] }}</h1>
            <p class="mt-5 max-w-2xl text-lg text-muted">{{ $home['hero_text'] }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('works.index') }}" class="btn-primary px-6">Explorer les travaux <x-icon name="arrow-right" class="size-4" /></a>
                <a href="{{ route('meet') }}" class="btn-secondary px-6">Découvrir le projet</a>
            </div>
        </div>
        @if ($now)
            <aside aria-labelledby="a-lire" class="relative">
                <p id="a-lire" class="mb-3 inline-flex items-center gap-2 text-sm font-bold"><span class="inline-block size-2 rounded-full bg-accent motion-safe:animate-pulse" aria-hidden="true"></span>À lire maintenant</p>
                <x-site.work-card :content="$now" sizes="(min-width: 1024px) 40vw, 100vw" />
            </aside>
        @endif
    </section>

    {{-- Trois piliers éditoriaux (éditables : page « home ») --}}
    <section class="border-y border-line bg-surface" aria-label="Nos piliers">
        <ul class="mx-auto grid max-w-7xl gap-px px-4 sm:px-6 md:grid-cols-3 lg:px-8">
            @foreach ($home['pillars'] as $i => $pillar)
                <li x-data="reveal" x-bind:class="revealClass" class="py-8 transition duration-700 md:px-6 md:first:pl-0">
                    <p class="font-serif text-4xl font-bold text-accent-ink dark:text-accent" aria-hidden="true">0{{ $i + 1 }}</p>
                    <h2 class="mt-1 text-xl font-bold">{{ $pillar['title'] }}</h2>
                    <p class="mt-2 text-sm text-muted">{{ $pillar['text'] }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    @if (! $now)
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <x-empty-state icon="article" title="Les premières publications arrivent bientôt">
                Revenez dans quelques jours, ou inscrivez-vous à la newsletter pour être prévenu.
            </x-empty-state>
        </section>
    @else
        {{-- À la une : dernière publication + dernier rapport + dernière série --}}
        <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8" aria-labelledby="une">
            <x-site.section-title eyebrow="À la une" title="Les derniers travaux" id="une" :link="route('works.index')" link-label="Toutes les publications" />
            <div class="grid gap-6 lg:grid-cols-3">
                <article class="relative overflow-hidden card lg:col-span-2 lg:grid lg:grid-cols-2">
                    <div class="aspect-[16/10] bg-surface-2 lg:aspect-auto"><x-content-cover :content="$now" sizes="(min-width: 1024px) 33vw, 100vw" /></div>
                    <div class="flex flex-col p-6">
                        <p class="text-xs font-semibold uppercase tracking-wider text-accent-2-ink">Dernière publication · {{ $now->type->label() }}</p>
                        <h3 class="mt-2 text-2xl font-bold"><a class="after:absolute after:inset-0" href="{{ $now->publicUrl() }}">{{ $now->title }}</a></h3>
                        <p class="mt-2 text-sm text-muted"><time datetime="{{ $now->displayDate()->toDateString() }}">{{ $now->displayDate()->translatedFormat('j F Y') }}</time> · {{ $now->reading_time }} min de lecture</p>
                        <p class="mt-4 text-muted">{{ $now->excerpt(260) }}</p>
                    </div>
                </article>
                <div class="grid gap-6">
                    @foreach ([['Dernier rapport', $lastReport, 'report'], ['Dernière vidéo', $lastSeries, 'monitor']] as [$label, $item, $icon])
                        <article class="relative flex gap-4 card p-5">
                            <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-accent-2/15 text-accent-2-ink"><x-icon :name="$icon" /></span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted">{{ $label }}</p>
                                @if ($item)
                                    <h3 class="mt-1 font-bold leading-snug"><a class="after:absolute after:inset-0" href="{{ $item->publicUrl() }}">{{ $item->title }}</a></h3>
                                    <p class="mt-1 text-xs text-muted">{{ $item->displayDate()->translatedFormat('j F Y') }}</p>
                                @else
                                    <p class="mt-1 text-sm text-muted">Bientôt disponible.</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Réflexion de la semaine : masquée si vide (cahier § 5.9) --}}
        @if ($reflection)
            <section class="bg-surface-2" aria-labelledby="reflexion">
                <figure class="mx-auto max-w-4xl px-4 py-14 text-center sm:px-6">
                    <h2 id="reflexion" class="text-xs font-bold uppercase tracking-[0.25em] text-accent-ink dark:text-accent">Réflexion de la semaine</h2>
                    <blockquote class="mt-5 font-serif text-2xl leading-relaxed sm:text-3xl">« {{ $reflection['text'] }} »</blockquote>
                    @if (! empty($reflection['author']))<figcaption class="mt-4 font-semibold text-muted">— {{ $reflection['author'] }}</figcaption>@endif
                </figure>
            </section>
        @endif

        {{-- Travaux mis en avant (piloté par « mise en avant », bug n° 4) --}}
        @if ($featured->isNotEmpty())
            <section class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8" aria-labelledby="avant">
                <x-site.section-title eyebrow="Sélection" title="Travaux mis en avant" id="avant" />
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($featured as $item)
                        <x-site.work-card :content="$item" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Dernières publications : 6 lignes --}}
        <section class="mx-auto max-w-7xl px-4 pb-6 sm:px-6 lg:px-8" aria-labelledby="dernieres">
            <x-site.section-title eyebrow="Fil" title="Dernières publications" id="dernieres" :link="route('works.index')" link-label="Voir tout" />
            <ol class="divide-y divide-line border-y border-line">
                @foreach ($recent as $item)
                    <li class="relative grid gap-1 py-4 sm:grid-cols-[12rem_1fr_auto] sm:items-baseline sm:gap-6">
                        <span class="text-xs font-semibold uppercase tracking-wider text-accent-2-ink">{{ $item->type->label() }}</span>
                        <a href="{{ $item->publicUrl() }}" class="font-serif text-lg font-bold hover:underline after:absolute after:inset-0">{{ $item->title }}</a>
                        <time class="text-sm text-muted" datetime="{{ $item->displayDate()->toDateString() }}">{{ $item->displayDate()->translatedFormat('j M Y') }}</time>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
</x-layouts.public>
