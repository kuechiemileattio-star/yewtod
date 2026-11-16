{{-- Couverture d'un contenu : image adaptative (srcset) ou couverture par défaut stable (cahier § 5.2). --}}
@props(['content', 'sizes' => '(min-width: 1024px) 33vw, 100vw', 'eager' => false])
@if ($content->cover_path)
    <img src="{{ $content->coverUrl() }}" srcset="{{ $content->coverSrcset() }}" sizes="{{ $sizes }}" alt="{{ $content->cover_alt ?? '' }}"
         @if (! $eager) loading="lazy" decoding="async" @endif {{ $attributes->merge(['class' => 'h-full w-full object-cover']) }}>
@else
    @php($tone = $content->coverTone())
    @php($toneClass = ['accent' => 'bg-accent/20', 'accent-2' => 'bg-accent-2/20', 'warning' => 'bg-warning/25'][$tone] ?? 'bg-surface-2')
    <div {{ $attributes->merge(['class' => 'relative flex h-full w-full items-end overflow-hidden p-4 '.$toneClass]) }} aria-hidden="true">
        <svg class="absolute -right-6 -top-6 size-40 opacity-40" viewBox="0 0 100 100"><circle cx="50" cy="50" r="48" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="50" cy="50" r="30" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="50" cy="50" r="12" fill="currentColor"/></svg>
        <span class="relative font-serif text-sm font-bold uppercase tracking-widest text-ink">{{ $content->type->label() }}</span>
    </div>
@endif
