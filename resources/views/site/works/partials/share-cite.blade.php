{{-- Bloc « Partager et citer » (cahier § 2.4, § 10 P2 : APA + BibTeX). --}}
@php
    $url = $content->publicUrl();
    $shares = [
        ['x', 'X', 'https://twitter.com/intent/tweet?'.http_build_query(['url' => $url, 'text' => $content->title])],
        ['linkedin', 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?'.http_build_query(['url' => $url])],
        ['facebook', 'Facebook', 'https://www.facebook.com/sharer/sharer.php?'.http_build_query(['u' => $url])],
    ];
@endphp
<section class="card mt-12 p-6 no-print" aria-labelledby="partager">
    <h2 id="partager" class="text-2xl font-bold">Partager et citer</h2>
    <div class="mt-4 flex flex-wrap gap-2">
        <span x-data="shareNative" data-title="{{ $content->title }}" data-url="{{ $url }}">
            <button type="button" class="btn-secondary" x-on:click="share"><x-icon name="share" class="size-4" /> <span x-text="label">Partager</span></button>
        </span>
        @foreach ($shares as [$icon, $label, $href])
            <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" class="btn-secondary" aria-label="Partager sur {{ $label }} (nouvelle fenêtre)"><x-social-icon :name="$icon" class="size-4" /> {{ $label }}</a>
        @endforeach
    </div>
    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ([['APA', $content->citation()], ['BibTeX', $content->bibtex()]] as [$format, $text])
            <figure class="min-w-0 rounded-xl border border-line bg-page p-4">
                <figcaption class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-muted">{{ $format }}</span>
                    <span x-data="copyText" data-text="{{ $text }}">
                        <button type="button" class="btn-ghost min-h-11 px-3 text-xs" x-on:click="copy">
                            <x-icon name="quote" class="size-4" /> <span x-show="!copied">Copier</span><span x-show="copied" x-cloak>Copié</span>
                        </button>
                    </span>
                </figcaption>
                <pre class="mt-2 whitespace-pre-wrap break-words font-mono text-xs">{{ $text }}</pre>
            </figure>
        @endforeach
    </div>
</section>
