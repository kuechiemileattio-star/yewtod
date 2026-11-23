@props(['title' => 'Espace membres', 'wide' => false])
<!DOCTYPE html>
<html lang="fr" class="{{ \App\Support\Theme::htmlClass() }}">
<head>
    @include('partials.head')
    <title>{{ $title }} — {{ config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="min-h-dvh">
    <a href="#contenu" class="skip-link">Aller au contenu</a>
    <div class="flex min-h-dvh flex-col items-center justify-center px-4 py-10 sm:px-6">
        <main id="contenu" @class(['page-enter w-full rounded-[14px] border border-line bg-page p-6 shadow-[0_24px_50px_-34px_var(--navy)] sm:p-9', 'max-w-[420px]' => ! $wide, 'max-w-2xl' => $wide])>
            <a href="{{ url('/') }}" class="mb-[26px] inline-flex min-h-11 items-center gap-2 font-serif text-2xl text-ink">
                <svg width="31" height="12" viewBox="0 0 26 10" aria-hidden="true"><circle cx="3" cy="5" r="2.6" fill="var(--accent)"/><line x1="5.5" y1="5" x2="20.5" y2="5" stroke="var(--accent)" stroke-width="1" opacity=".55"/><circle cx="23" cy="5" r="2.6" fill="none" stroke="var(--accent-2)" stroke-width="1.3"/></svg>{{ config('app.name') }}
            </a>
            <p class="kicker text-accent-ink">Espace privé</p>
            <h1 class="mb-[26px] mt-2 text-[28px] font-medium">{{ $title }}</h1>
            @if (session('status'))
                <div role="status" class="mb-5 rounded-lg border border-success/40 bg-success/10 p-3 text-sm text-ink">{{ session('status') }}</div>
            @endif
            @if (session('warning'))
                <div role="alert" class="mb-5 rounded-lg border border-warning bg-warning/15 p-3 text-sm text-ink">{{ session('warning') }}</div>
            @endif
            {{ $slot }}
        </main>
        <p class="mt-6 text-center text-[12.5px] text-muted">Cet espace est réservé aux membres invités. <a class="link" href="{{ url('/') }}">Retour au site</a></p>
    </div>
    @livewireScriptConfig(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])
</body>
</html>
