{{-- En-tête commun : métadonnées, thème sans flash, assets compilés. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="color-scheme" content="light dark">
@if (\App\Support\Theme::current() === 'system')
    {{-- Seul script inline du site : autorisé par le nonce CSP, il applique
         le thème « système » avant le premier rendu (pas de flash blanc). --}}
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">if (window.matchMedia('(prefers-color-scheme: dark)').matches) { document.documentElement.classList.add('dark'); }</script>
@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
