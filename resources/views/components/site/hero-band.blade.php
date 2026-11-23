{{-- Bandeau de titre bleu nuit des pages de liste (référence, D78) : fil d'Ariane, titre, sous-titre. --}}
@props(['title', 'crumb' => null, 'text' => null])
<section {{ $attributes->merge(['class' => 'bg-navy text-white']) }}>
    <div class="mx-auto max-w-[1280px] px-6 pb-[34px] pt-10">
        <nav aria-label="Fil d'Ariane" class="mb-2.5 text-xs text-on-navy-muted">
            <a href="{{ url('/') }}" class="hover:text-white hover:underline">Accueil</a> <span aria-hidden="true">›</span> <span aria-current="page">{{ $crumb ?? $title }}</span>
        </nav>
        <h1 class="text-[clamp(1.9rem,4vw,2.6rem)] text-white">{{ $title }}</h1>
        @if ($text)<p class="mt-2 text-[14.5px] text-on-navy/80">{{ $text }}</p>@endif
        {{ $slot }}
    </div>
</section>
