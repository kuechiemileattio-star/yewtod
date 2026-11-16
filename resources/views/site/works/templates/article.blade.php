{{-- Gabarit « Article » (cahier § 2.4.1). --}}
@php
    $theme = $content->themes[0] ?? null;
    $kind = $content->meta['content_kind'] ?? null;
@endphp
<article>
    <header class="relative isolate overflow-hidden bg-navy text-white">
        @if ($content->cover_path)
            <x-content-cover :content="$content" sizes="100vw" :eager="true" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-35" />
        @endif
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-navy via-navy/85 to-navy/40" aria-hidden="true"></div>
        <div class="mx-auto max-w-5xl px-4 pb-12 pt-14 sm:px-6 lg:pt-20">
            <nav aria-label="Fil d'Ariane" class="text-sm text-white/80"><a class="hover:underline" href="{{ url('/works') }}">Travaux</a> / <a class="hover:underline" href="{{ url('/works').'?categorie=articles' }}">Articles</a></nav>
            <ul class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                <li class="badge bg-accent text-navy">Article</li>
                @if ($kind && isset(\App\Support\Taxonomy::ARTICLE_KINDS[$kind]))<li class="badge bg-white/15">{{ \App\Support\Taxonomy::ARTICLE_KINDS[$kind] }}</li>@endif
                <li class="badge bg-white/15">{{ \App\Support\Taxonomy::LANGUAGES[$content->language] ?? 'Français' }}</li>
                @if ($theme && isset(\App\Support\Taxonomy::THEMES[$theme]))<li class="badge bg-white/15">{{ \App\Support\Taxonomy::THEMES[$theme] }}</li>@endif
            </ul>
            <h1 class="mt-4 text-4xl font-bold leading-tight sm:text-5xl">{{ $content->title }}</h1>
            @if (! empty($content->meta['subtitle']))<p class="mt-3 text-xl text-white/85">{{ $content->meta['subtitle'] }}</p>@endif
            <p class="mt-5 text-sm text-white/85">
                Par <strong class="text-white">{{ $content->displayAuthor() }}</strong> ·
                <time datetime="{{ $content->displayDate()->toDateString() }}">{{ $content->displayDate()->translatedFormat('j F Y') }}</time> ·
                {{ $content->reading_time }} min de lecture
            </p>
            <div class="mt-6 flex flex-wrap gap-2 no-print">
                <span x-data="copyText" data-text="{{ $content->citation() }}">
                    <button type="button" x-on:click="copy" class="btn bg-white text-navy hover:bg-white/90"><x-icon name="quote" class="size-4" /> <span x-show="!copied">Citer</span><span x-show="copied" x-cloak>Référence copiée</span></button>
                </span>
                @foreach ([['x', 'X', 'https://twitter.com/intent/tweet?'.http_build_query(['url' => $content->publicUrl(), 'text' => $content->title])], ['linkedin', 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?'.http_build_query(['url' => $content->publicUrl()])], ['facebook', 'Facebook', 'https://www.facebook.com/sharer/sharer.php?'.http_build_query(['u' => $content->publicUrl()])]] as [$icon, $label, $href])
                    <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" class="btn min-w-11 border border-white/40 px-3 text-white hover:bg-white/10" aria-label="Partager sur {{ $label }} (nouvelle fenêtre)"><x-social-icon :name="$icon" class="size-4" /></a>
                @endforeach
                @if ($content->downloadUrl())
                    <a href="{{ $content->downloadUrl() }}" class="btn border border-white/40 text-white hover:bg-white/10"><x-icon name="download" class="size-4" /> Télécharger le PDF</a>
                @endif
            </div>
        </div>
    </header>

    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1fr_18rem] lg:px-8">
        <div class="min-w-0">
            @if ($content->summary)
                <p class="mb-10 border-l-4 border-accent pl-5 font-serif text-xl leading-relaxed">{{ $content->summary }}</p>
            @endif
            @include('site.works.partials.sections')
            @include('site.works.partials.type-fields')
            @includeIf('partials.public.reviews', ['reviewable' => $content])
            @include('site.works.partials.share-cite')
        </div>

        <aside class="space-y-6" aria-label="À propos de cette publication">
            <div class="card p-5">
                <p class="text-xs font-bold uppercase tracking-wider text-muted">Auteur</p>
                <div class="mt-3 flex items-center gap-3">
                    @if ($content->author?->avatarUrl())
                        <img src="{{ $content->author->avatarUrl() }}" alt="" class="size-12 rounded-full object-cover">
                    @else
                        <span class="inline-flex size-12 items-center justify-center rounded-full bg-accent-2/20 text-lg font-bold text-accent-2-ink" aria-hidden="true">{{ mb_strtoupper(mb_substr($content->displayAuthor(), 0, 1)) }}</span>
                    @endif
                    <p class="font-semibold">{{ $content->displayAuthor() }}</p>
                </div>
                @if ($content->author?->bio)<p class="mt-3 text-sm text-muted">{{ \Illuminate\Support\Str::limit($content->author->bio, 240) }}</p>@endif
            </div>
            <dl class="card grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 p-5 text-sm">
                <dt class="text-muted">Date</dt><dd>{{ $content->displayDate()->translatedFormat('j F Y') }}</dd>
                <dt class="text-muted">Type</dt><dd>Article</dd>
                @if ($theme && isset(\App\Support\Taxonomy::THEMES[$theme]))<dt class="text-muted">Axe</dt><dd>{{ \App\Support\Taxonomy::THEMES[$theme] }}</dd>@endif
                <dt class="text-muted">Langue</dt><dd>{{ \App\Support\Taxonomy::LANGUAGES[$content->language] ?? $content->language }}</dd>
                <dt class="text-muted">Vues</dt><dd>{{ number_format($content->views_count, 0, ',', ' ') }}</dd>
            </dl>
            @if ($alsoRead->isNotEmpty())
                <nav class="card p-5" aria-labelledby="aussi">
                    <h2 id="aussi" class="text-xs font-bold uppercase tracking-wider text-muted">À lire aussi</h2>
                    <ul class="mt-2 divide-y divide-line">
                        @foreach ($alsoRead as $item)
                            <li><a href="{{ $item->publicUrl() }}" class="block py-3 font-serif font-bold leading-snug hover:underline">{{ $item->title }}<span class="mt-1 block font-sans text-xs font-normal text-muted">{{ $item->displayDate()->translatedFormat('j M Y') }}</span></a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </aside>
    </div>
</article>
@include('site.works.partials.associated')
