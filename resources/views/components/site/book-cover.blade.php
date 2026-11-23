{{-- Couverture de livre (format 2:3, ou remplissage du cadre parent avec fill) ou couverture par défaut. --}}
@props(['book', 'sizes' => '(min-width: 1024px) 20vw, 50vw', 'eager' => false, 'fill' => false])
@php($shape = $fill ? 'h-full w-full' : 'aspect-[2/3] w-full')
@if ($book->cover_path)
    <img src="{{ $book->coverUrl() }}" srcset="{{ $book->coverSrcset() }}" sizes="{{ $sizes }}" alt="{{ $book->cover_alt ?? 'Couverture de '.$book->title }}"
         @if (! $eager) loading="lazy" decoding="async" @endif {{ $attributes->merge(['class' => $shape.' object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => $shape.' flex flex-col justify-between bg-navy p-4 text-white']) }} aria-hidden="true">
        <span class="font-mono text-[10px] uppercase tracking-[0.14em] text-amber">{{ $book->categoryLabel() }}</span>
        <span class="font-serif text-lg leading-tight">{{ $book->title }}</span>
        <span class="text-xs text-on-navy-muted">{{ $book->author }}</span>
    </div>
@endif
