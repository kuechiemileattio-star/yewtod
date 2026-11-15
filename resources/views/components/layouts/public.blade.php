{{--
    Gabarit du site public (cahier § 2.1, § 9, § 10) : rendu serveur, SEO
    (titre, description, canonique, Open Graph, Twitter, JSON-LD), thème sans
    flash, lien d'évitement, navigation responsive.
--}}
@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'image' => null,
    'ogType' => 'website',
    'jsonLd' => null,
    'noindex' => false,
])
@php
    $site = app(\App\Services\SiteSettings::class);
    $siteName = $site->siteName();
    $fullTitle = $title ? $title.' — '.$siteName : $siteName;
    $desc = \Illuminate\Support\Str::limit(strip_tags((string) ($description ?: $site->get('seo_description'))), 160);
    $canonicalUrl = $canonical ?: url()->current();
@endphp
<!DOCTYPE html>
<html lang="fr" class="{{ \App\Support\Theme::htmlClass() }}">
<head>
    @include('partials.head')
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $desc }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if ($noindex)<meta name="robots" content="noindex, follow">@endif
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if ($image)<meta property="og:image" content="{{ $image }}"><meta name="twitter:image" content="{{ $image }}">@endif
    <meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $title ?? $siteName }}">
    <meta name="twitter:description" content="{{ $desc }}">
    @if ($site->faviconUrl())<link rel="icon" href="{{ $site->faviconUrl() }}">@endif
    @if (\Illuminate\Support\Facades\Route::has('feed'))<link rel="alternate" type="application/rss+xml" title="{{ $siteName }}" href="{{ route('feed') }}">@endif
    @if ($jsonLd)
        {{-- Données structurées : JSON encodé côté serveur (échappement de </ via JSON_HEX_TAG). --}}
        <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif
</head>
<body class="flex min-h-dvh flex-col">
    <a href="#contenu" class="skip-link">Aller au contenu</a>
    @include('partials.public.nav', ['siteName' => $siteName, 'logo' => $site->logoUrl()])
    <main id="contenu" class="page-enter flex-1" tabindex="-1">
        {{ $slot }}
    </main>
    @include('partials.public.footer', ['siteName' => $siteName, 'socials' => $site->socialLinks(), 'footer' => $site->page('footer')])
    <x-toasts />
    @livewireScriptConfig
</body>
</html>
