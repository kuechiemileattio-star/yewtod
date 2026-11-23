{{--
    Cadre de couverture de la référence (D78) : bordure fine, dégradé sombre en
    bas, étiquette du type en capitales. « label » vaut par défaut le type au
    pluriel (« Articles », « Rapports »…), comme sur la référence.
--}}
@props(['content', 'sizes' => '(min-width: 1024px) 33vw, 100vw', 'eager' => false, 'label' => null, 'height' => 'h-[168px]'])
<div {{ $attributes->merge(['class' => 'cover-frame group/cover relative overflow-hidden rounded-[2px] border border-line bg-surface-2 '.$height]) }}>
    <x-content-cover :content="$content" :sizes="$sizes" :eager="$eager" class="absolute inset-0 h-full w-full object-cover opacity-[.86] saturate-[.78] contrast-[.96] transition duration-700 group-hover:scale-[1.06] group-hover/cover:scale-[1.06] dark:opacity-80" />
    <div class="absolute inset-0 bg-gradient-to-b from-transparent from-35% to-[#172238]/60" aria-hidden="true"></div>
    @if ($label !== false)
        <span class="cover-label absolute bottom-3 left-3 transition duration-300 group-hover:translate-x-1 group-hover:border-amber group-hover:bg-amber group-hover:text-[#172238]">{{ $label ?? $content->type->plural() }}</span>
    @endif
</div>
