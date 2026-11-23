{{-- Accueil (cahier § 2.2), mise en page de la référence (D78) ; données calculées par HomeController. --}}
<x-layouts.public :description="$home['hero_text']" :json-ld="[
    '@context' => 'https://schema.org', '@type' => 'WebSite',
    'name' => app(\App\Services\SiteSettings::class)->siteName(), 'url' => url('/'),
    'potentialAction' => ['@type' => 'SearchAction', 'target' => url('/works').'?q={search_term_string}', 'query-input' => 'required name=search_term_string'],
]">
    <div class="overflow-x-clip">
    {{-- Accroche + « À lire maintenant » (carte inclinée sur fond bleu nuit) --}}
    <section class="mx-auto grid max-w-[1120px] items-center gap-14 px-6 pb-14 pt-12 lg:grid-cols-[1.05fr_.95fr] lg:pb-16 lg:pt-[72px]" aria-labelledby="accroche">
        <div class="stagger max-w-[720px]">
            <h1 id="accroche" class="text-[clamp(2.4rem,5.5vw,3.9rem)] leading-[1.06]">{{ $home['hero_title'] }}</h1>
            <p class="mt-6 max-w-[620px] text-lg leading-relaxed text-muted">{{ $home['hero_text'] }}</p>
            <div class="mt-8 flex flex-wrap gap-3.5">
                <a href="{{ route('works.index') }}" class="btn-primary">Explorer les travaux <x-icon name="arrow-right" class="size-4" /></a>
                <a href="{{ route('meet') }}" class="btn-secondary">Découvrir le projet</a>
            </div>
        </div>
        @if ($now)
            <aside aria-labelledby="a-lire" class="relative mx-auto w-full max-w-[520px] px-3 pt-3 sm:px-0">
                <span class="pointer-events-none absolute -inset-x-6 -bottom-6 top-[-34px] hidden rounded-full border border-accent-2/30 sm:block" aria-hidden="true"></span>
                <span class="absolute inset-[12px_0_0_36px] rotate-[3deg] bg-navy" aria-hidden="true"></span>
                <article class="group relative -rotate-[3deg] border border-line bg-surface-2 p-3 transition duration-500 hover:-rotate-1">
                    <x-site.cover :content="$now" height="h-[220px] sm:h-[260px]" sizes="(min-width: 1024px) 480px, 100vw" :eager="true" />
                    <div class="px-2 pb-1 pt-3.5">
                        <p id="a-lire" class="kicker text-[10px]">À lire maintenant</p>
                        <h2 class="mt-1.5 text-[22px] leading-tight"><a href="{{ $now->publicUrl() }}" class="after:absolute after:inset-0">{{ $now->title }}</a></h2>
                    </div>
                </article>
            </aside>
        @endif
    </section>

    {{-- Ce qu'on trouve ici : trois piliers éditoriaux (éditables : page « home ») --}}
    <section class="mx-auto max-w-[1120px] px-6 pb-[72px] pt-2.5" aria-labelledby="piliers">
        <div class="mb-6 grid items-end gap-4 md:grid-cols-[.7fr_1.3fr] md:gap-8">
            <p class="kicker text-[10px]">Ce qu'on trouve ici</p>
            <h2 id="piliers" class="max-w-[700px] text-[clamp(1.75rem,4vw,2.75rem)] font-normal leading-[1.04] tracking-[-0.04em]">Pas de raccourcis, mais pas non plus de jargon inutile.</h2>
        </div>
        <ul class="grid gap-3.5 md:grid-cols-3">
            @foreach ($home['pillars'] as $i => $pillar)
                <li x-data="reveal" x-bind:class="revealClass" class="reveal-delay-{{ $i }}">
                    <article class="group card card-hover h-full min-h-[190px] p-[22px]">
                        <span class="grid size-10 place-items-center rounded-full bg-navy text-white transition duration-300 group-hover:rotate-12 group-hover:scale-110 group-hover:bg-accent-strong dark:bg-navy-2" aria-hidden="true">
                            <x-icon :name="['compass', 'network', 'lightbulb'][$i] ?? 'compass'" class="size-5" />
                        </span>
                        <h3 class="mt-[22px] text-[23px]">{{ $pillar['title'] }}</h3>
                        <p class="mt-1.5 text-[13px] leading-relaxed text-muted">{{ $pillar['text'] }}</p>
                    </article>
                </li>
            @endforeach
        </ul>
    </section>

    @if (! $now)
        <section class="mx-auto max-w-[1120px] px-6 py-16">
            <x-empty-state icon="article" title="Les premières publications arrivent bientôt">
                Revenez dans quelques jours, ou inscrivez-vous à la newsletter pour être prévenu.
            </x-empty-state>
        </section>
    @else
        <hr class="divider mx-auto mb-16 max-w-[1120px]">

        {{-- À la une : dernière publication, dernier rapport, dernière vidéo, réflexion --}}
        <section x-data="reveal" x-bind:class="revealClass" class="mx-auto grid max-w-[1120px] gap-12 px-6 pb-[72px] lg:grid-cols-[1.3fr_1fr]" aria-label="À la une">
            <article class="group relative">
                <x-site.cover :content="$now" height="h-[240px] sm:h-[300px]" sizes="(min-width: 1024px) 620px, 100vw" />
                <p class="meta mt-[18px]"><time datetime="{{ $now->displayDate()->toDateString() }}">{{ $now->displayDate()->translatedFormat('j F Y') }}</time>@if ($now->type === \App\Enums\ContentType::Article) · {{ $now->reading_time }} min @endif</p>
                <h2 class="mt-2.5 text-[clamp(1.6rem,3vw,1.9rem)] leading-tight"><a class="after:absolute after:inset-0 group-hover:underline group-hover:decoration-1" href="{{ $now->publicUrl() }}">{{ $now->title }}</a></h2>
                <p class="mt-2.5 text-[15.5px] leading-relaxed text-muted">{{ $now->excerpt(420) }}</p>
            </article>
            <div class="flex flex-col gap-8">
                @foreach ([['Dernier rapport', $lastReport], ['Dernière vidéo', $lastSeries]] as [$label, $item])
                    @if ($item)
                        <article class="group relative flex gap-4">
                            <x-site.cover :content="$item" class="w-[120px] shrink-0" height="h-[150px] sm:h-[168px]" sizes="120px" :label="false" />
                            <div class="min-w-0">
                                <p class="kicker text-[10.5px]">{{ $label }}</p>
                                <h3 class="mt-1.5 text-lg font-semibold leading-snug"><a class="after:absolute after:inset-0 group-hover:underline group-hover:decoration-1" href="{{ $item->publicUrl() }}">{{ $item->title }}</a></h3>
                                <p class="meta mt-1.5">{{ $item->displayDate()->translatedFormat('j F Y') }}</p>
                            </div>
                        </article>
                    @endif
                @endforeach
                {{-- Réflexion de la semaine : masquée si vide (cahier § 5.9) --}}
                @if ($reflection)
                    <figure class="border border-line border-l-[3px] border-l-accent-2 bg-surface-2 p-6" aria-labelledby="reflexion">
                        <h2 id="reflexion" class="font-mono text-[10.5px] font-normal uppercase tracking-[0.08em] text-accent-2-ink">Réflexion de la semaine</h2>
                        <blockquote class="mt-2.5 font-serif text-lg italic leading-normal">« {{ $reflection['text'] }} »</blockquote>
                        @if (! empty($reflection['author']))<figcaption class="meta mt-2.5">— {{ $reflection['author'] }}</figcaption>@endif
                    </figure>
                @endif
            </div>
        </section>

        {{-- Travaux mis en avant (piloté par « mise en avant », bug n° 4) --}}
        @if ($featured->isNotEmpty())
            <section x-data="reveal" x-bind:class="revealClass" class="relative mx-auto max-w-[1120px] px-6 pb-[72px] pt-7" aria-labelledby="avant">
                <div class="pointer-events-none absolute inset-x-0 bottom-7 top-0 -z-10 rounded-[20px] bg-gradient-to-br from-surface-2/60 to-transparent to-70%" aria-hidden="true"></div>
                <h2 id="avant" class="section-label mb-7">Travaux mis en avant</h2>
                <ul class="grid gap-7 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($featured as $item)
                        <li><x-site.work-card :content="$item" sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw" /></li>
                    @endforeach
                </ul>
            </section>
        @endif

        <hr class="divider mx-auto mb-[72px] max-w-[1120px]">

        {{-- Dernières publications : 6 lignes --}}
        <section x-data="reveal" x-bind:class="revealClass" class="mx-auto max-w-[1120px] px-6" aria-labelledby="dernieres">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h2 id="dernieres" class="section-label">Dernières publications</h2>
                <a href="{{ route('works.index') }}" class="link inline-flex min-h-11 items-center gap-1 text-sm">Voir tout <x-icon name="arrow-right" class="size-4" /></a>
            </div>
            <ol class="border-t border-line">
                @foreach ($recent as $item)
                    <li class="group relative flex flex-col gap-2 border-b border-line py-5 transition-all duration-200 hover:bg-surface-2/80 hover:pl-2 sm:flex-row sm:items-center sm:justify-between sm:gap-5">
                        <div class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center sm:gap-5">
                            <span class="pill w-fit shrink-0">{{ $item->type->plural() }}</span>
                            <a href="{{ $item->publicUrl() }}" class="font-serif text-lg font-medium leading-snug after:absolute after:inset-0 sm:truncate">{{ $item->title }}</a>
                        </div>
                        <time class="meta shrink-0" datetime="{{ $item->displayDate()->toDateString() }}">{{ $item->displayDate()->translatedFormat('j F Y') }}</time>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
    </div>
</x-layouts.public>
