{{-- Couverture de livre (format 2:3) ou couverture par défaut. --}}
@props(['book', 'sizes' => '(min-width: 1024px) 20vw, 50vw', 'eager' => false])
@if ($book->cover_path)
    <img src="{{ $book->coverUrl() }}" srcset="{{ $book->coverSrcset() }}" sizes="{{ $sizes }}" alt="{{ $book->cover_alt ?? 'Couverture de '.$book->title }}"
         @if (! $eager) loading="lazy" decoding="async" @endif {{ $attributes->merge(['class' => 'aspect-[2/3] w-full object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'flex aspect-[2/3] w-full flex-col justify-between bg-navy p-4 text-white']) }} aria-hidden="true">
        <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-accent">{{ $book->categoryLabel() }}</span>
        <span class="font-serif text-lg font-bold leading-tight">{{ $book->title }}</span>
        <span class="text-xs opacity-80">{{ $book->author }}</span>
    </div>
@endif
