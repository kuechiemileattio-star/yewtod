{{--
    Carte de publication (cahier § 2.3) : la carte entière est cliquable grâce
    à un VRAI lien (titre) étendu par un pseudo-élément — accessible au clavier
    et aux lecteurs d'écran (cahier § 10 P1), contrairement aux div cliquables.
--}}
@props(['content', 'headingLevel' => 'h3', 'sizes' => '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw'])
@php
    $theme = $content->themes[0] ?? null;
    $author = $content->displayAuthor();
@endphp
<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden card transition hover:-translate-y-0.5 hover:shadow-lg focus-within:ring-2 focus-within:ring-focus']) }}>
    <div class="aspect-[16/9] overflow-hidden bg-surface-2">
        <x-content-cover :content="$content" :sizes="$sizes" class="transition duration-500 group-hover:scale-[1.02]" />
    </div>
    <div class="flex flex-1 flex-col p-5">
        <p class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wider text-accent-2-ink">
            <span>{{ $content->type->label() }}</span>
            @if ($theme && isset(\App\Support\Taxonomy::THEMES[$theme]))<span aria-hidden="true">·</span><span class="text-muted">{{ \App\Support\Taxonomy::THEMES[$theme] }}</span>@endif
        </p>
        <{{ $headingLevel }} class="mt-2 text-xl font-bold leading-snug">
            <a href="{{ $content->publicUrl() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $content->title }}</a>
        </{{ $headingLevel }}>
        @if ($content->excerpt())
            <p class="mt-2 text-sm text-muted">{{ $content->excerpt(140) }}</p>
        @endif
        <div class="mt-auto pt-4">
            <p class="flex items-center gap-2 text-sm">
                <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-accent-2/20 font-bold text-accent-2-ink" aria-hidden="true">{{ mb_strtoupper(mb_substr($author, 0, 1)) }}</span>
                <span class="min-w-0 truncate font-semibold">{{ $author }}</span>
            </p>
            <p class="mt-2 text-xs text-muted">
                <time datetime="{{ $content->displayDate()->toDateString() }}">{{ $content->displayDate()->translatedFormat('j F Y') }}</time>
                @if ($content->type === \App\Enums\ContentType::Article) · {{ $content->reading_time }} min de lecture @endif
            </p>
            @if ($content->tags)
                <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Mots-clés">
                    @foreach (array_slice($content->tags, 0, 3) as $tag)
                        <li class="badge bg-surface-2 text-muted">#{{ $tag }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</article>
