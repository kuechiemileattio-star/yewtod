{{-- Lecteur vidéo (cahier § 3 « Vidéos ») : YouTube nocookie arrêté à 2 min, Vimeo, MP4/WebM. --}}
@php($video = \App\Support\VideoEmbed::parse($url ?? null))
@if ($video)
    <figure class="overflow-hidden card">
        <div class="aspect-video bg-ink">
            @if ($video['kind'] === 'file')
                <video src="{{ $video['embed'] }}" controls preload="metadata" class="h-full w-full"></video>
            @else
                <iframe src="{{ $video['embed'] }}" title="{{ $label ?? 'Vidéo' }}" class="h-full w-full" loading="lazy"
                        allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            @endif
        </div>
        @if ($video['kind'] === 'youtube')
            <figcaption class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                <span class="text-muted"><span class="badge bg-warning/25 text-warning-ink">Aperçu gratuit</span> La lecture s'arrête après {{ intdiv(\App\Support\VideoEmbed::PREVIEW_SECONDS, 60) }} minutes.</span>
                <a href="{{ $video['watch'] }}" target="_blank" rel="noopener noreferrer" class="btn-primary">Continuer sur YouTube <x-icon name="external" class="size-4" /></a>
            </figcaption>
        @endif
    </figure>
@endif
