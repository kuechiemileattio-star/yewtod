{{--
    Couverture d'un contenu (cahier § 5.2), au style de la référence (D78) :
    image légèrement désaturée, ou fond dégradé de la couleur du type avec des
    lignes et des points discrets quand il n'y a pas d'image. Le cadre
    (hauteur, étiquette) est géré par <x-site.cover>.
--}}
@props(['content', 'sizes' => '(min-width: 1024px) 33vw, 100vw', 'eager' => false])
@if ($content->cover_path)
    <img src="{{ $content->coverUrl() }}" srcset="{{ $content->coverSrcset() }}" sizes="{{ $sizes }}" alt="{{ $content->cover_alt ?? '' }}"
         @if (! $eager) loading="lazy" decoding="async" @endif {{ $attributes->merge(['class' => 'h-full w-full object-cover']) }}>
@else
    @php($tone = $content->coverTone())
    @php([$bg, $stroke] = ['accent' => ['from-accent/10 via-accent/20 to-accent/5', 'text-accent'], 'accent-2' => ['from-accent-2/10 via-accent-2/20 to-accent-2/5', 'text-accent-2'], 'warning' => ['from-amber/15 via-amber/25 to-amber/10', 'text-amber']][$tone] ?? ['from-surface-2 to-surface-2', 'text-muted'])
    <div {{ $attributes->merge(['class' => 'relative h-full w-full overflow-hidden bg-gradient-to-br '.$bg]) }} aria-hidden="true">
        <svg class="absolute inset-0 size-full {{ $stroke }}" viewBox="0 0 100 100" preserveAspectRatio="none">
            <line x1="0" y1="30" x2="100" y2="10" stroke="currentColor" stroke-width=".4" opacity=".45" vector-effect="non-scaling-stroke"/>
            <line x1="0" y1="75" x2="100" y2="55" stroke="currentColor" stroke-width=".4" opacity=".35" vector-effect="non-scaling-stroke"/>
        </svg>
        <span class="absolute left-[18%] top-[30%] size-1.5 rounded-full bg-current opacity-50 {{ $stroke }}"></span>
        <span class="absolute left-[82%] top-[10%] size-1.5 rounded-full bg-current opacity-50 {{ $stroke }}"></span>
        <span class="absolute left-[60%] top-[75%] size-1.5 rounded-full bg-current opacity-40 {{ $stroke }}"></span>
    </div>
@endif
