{{--
    Carte de publication (cahier § 2.3), au style de la référence (D78).
    - « compact » (accueil) : couverture, titre, date ;
    - « full » (catalogue) : carte encadrée avec axe, extrait, auteur, mots-clés.
    La carte entière est cliquable grâce à un VRAI lien (titre) étendu par un
    pseudo-élément : accessible au clavier et aux lecteurs d'écran (cahier § 10).
--}}
@props(['content', 'headingLevel' => 'h3', 'variant' => 'compact', 'sizes' => '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw'])
@php
    $theme = $content->themes[0] ?? null;
    $axis = $theme && isset(\App\Support\Taxonomy::THEMES[$theme]) ? \App\Support\Taxonomy::THEMES[$theme] : $content->type->plural();
    $author = $content->displayAuthor();
    $isArticle = $content->type === \App\Enums\ContentType::Article;
@endphp
@if ($variant === 'full')
    <article {{ $attributes->merge(['class' => 'group relative flex h-full flex-col overflow-hidden rounded-lg border border-line bg-card backdrop-blur-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-card focus-within:ring-2 focus-within:ring-focus']) }}>
        <x-site.cover :content="$content" :sizes="$sizes" class="rounded-none border-0 border-b" />
        <div class="flex flex-1 flex-col px-[18px] pb-[18px] pt-4">
            <p class="font-mono text-[10.5px] uppercase tracking-[0.04em] text-accent-2-ink">{{ $axis }}</p>
            <{{ $headingLevel }} class="mt-1.5 text-lg font-semibold leading-snug">
                <a href="{{ $content->publicUrl() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $content->title }}</a>
            </{{ $headingLevel }}>
            @if ($content->excerpt())
                <p class="mt-2 text-[13.5px] leading-relaxed text-muted">{{ $content->excerpt(140) }}</p>
            @endif
            <p class="meta mt-auto flex flex-wrap items-center gap-x-1.5 gap-y-1 pt-3.5">
                <span class="inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-navy font-sans text-[11px] font-bold text-white dark:bg-navy-2" aria-hidden="true">{{ mb_strtoupper(mb_substr($author, 0, 1)) }}</span>
                <span class="min-w-0 truncate font-sans font-semibold text-ink">{{ $author }}</span>
                <span aria-hidden="true">·</span>
                <time datetime="{{ $content->displayDate()->toDateString() }}">{{ $content->displayDate()->translatedFormat('j F Y') }}</time>
                @if ($isArticle)<span aria-hidden="true">·</span><span>{{ $content->reading_time }} min de lecture</span>@endif
            </p>
            @if ($content->tags)
                <ul class="relative z-10 mt-3 flex flex-wrap gap-1.5" aria-label="Mots-clés">
                    @foreach (array_slice($content->tags, 0, 3) as $tag)
                        <li class="pill">#{{ $tag }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </article>
@else
    <article {{ $attributes->merge(['class' => 'lift-card group relative focus-within:ring-2 focus-within:ring-focus focus-within:ring-offset-4 focus-within:ring-offset-page']) }}>
        <x-site.cover :content="$content" :sizes="$sizes" class="transition duration-500 group-hover:shadow-[0_14px_30px_-14px_rgb(23_34_56_/_0.35)]" />
        <{{ $headingLevel }} class="mt-3.5 text-[17px] font-semibold leading-[1.28]">
            <a href="{{ $content->publicUrl() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $content->title }}</a>
        </{{ $headingLevel }}>
        <p class="meta mt-2">
            <time datetime="{{ $content->displayDate()->toDateString() }}">{{ $content->displayDate()->translatedFormat('j F Y') }}</time>
            @if ($isArticle) · {{ $content->reading_time }} min @endif
        </p>
    </article>
@endif
