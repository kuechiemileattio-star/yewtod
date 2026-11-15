<x-layouts.guest title="Double authentification" :wide="true">
    @if ($enabled)
        <p class="mb-4 text-sm"><span class="badge bg-success/15 text-success">Activée</span> depuis le {{ $user->two_factor_confirmed_at->setTimezone(config('app.display_timezone'))->translatedFormat('j F Y') }}.</p>

        @if ($recoveryCodes)
            <section class="mb-6 rounded-xl border border-warning bg-warning/10 p-4" x-data="recoveryCodes" data-codes='@json($recoveryCodes)' aria-labelledby="codes-titre">
                <h2 id="codes-titre" class="mb-2 text-lg font-bold">Vos 8 codes de secours</h2>
                <p class="mb-3 text-sm">Ils ne seront <strong>plus jamais affichés</strong>. Chacun permet une connexion si vous perdez votre téléphone, une seule fois.</p>
                <ul class="mb-4 grid grid-cols-1 gap-2 font-mono text-sm xs:grid-cols-2">
                    @foreach ($recoveryCodes as $code)
                        <li class="rounded-md bg-surface px-3 py-2">{{ $code }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-secondary" x-on:click="download">Télécharger (.txt)</button>
            </section>
            <a href="{{ route('dashboard') }}" class="btn-primary mb-6 w-full sm:w-auto">J'ai conservé mes codes, accéder au tableau de bord</a>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row">
            <form method="POST" action="{{ route('security.two-factor.regenerate') }}">
                @csrf
                <button class="btn-secondary w-full sm:w-auto">Régénérer les codes de secours</button>
            </form>
            <form method="POST" action="{{ route('security.two-factor.disable') }}" x-data="confirmDialog" x-ref="form">
                @csrf @method('DELETE')
                <button type="button" class="btn-ghost w-full text-danger sm:w-auto" x-on:click="ask">Changer d'appareil (désactiver)</button>
                <x-confirm-dialog title="Désactiver la double authentification ?">
                    Vous devrez configurer un nouvel appareil immédiatement : le tableau de bord restera inaccessible d'ici là.
                </x-confirm-dialog>
            </form>
        </div>
    @elseif ($pending)
        <ol class="mb-6 list-decimal space-y-2 pl-5 text-sm">
            <li>Ouvrez votre application (Google Authenticator, Microsoft Authenticator, 2FAS, Bitwarden…).</li>
            <li>Scannez le QR code ou saisissez la clé manuellement.</li>
            <li>Saisissez le code à 6 chiffres affiché pour confirmer.</li>
        </ol>
        <div class="mb-6 flex flex-col items-center gap-4 sm:flex-row sm:items-start">
            {{-- SVG produit par le serveur (BaconQrCode) à partir du secret : aucun contenu utilisateur. --}}
            <div class="rounded-xl bg-white p-3" aria-label="QR code de configuration" role="img">{!! $qrSvg !!}</div>
            <div class="min-w-0 text-sm">
                <p class="mb-1 font-semibold">Clé de configuration</p>
                <p class="break-all rounded-md bg-surface-2 px-3 py-2 font-mono" x-data="copyText" data-text="{{ $secretKey }}">
                    {{ $secretKey }}
                    <button type="button" class="link ml-2 min-h-11" x-on:click="copy"><span x-show="!copied">Copier</span><span x-show="copied" x-cloak>Copié</span></button>
                </p>
            </div>
        </div>
        <form method="POST" action="{{ route('security.two-factor.confirm') }}" class="max-w-xs">
            @csrf
            <x-form.input name="code" label="Code de confirmation" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus />
            <button type="submit" class="btn-primary w-full">Confirmer l'activation</button>
        </form>
    @else
        <p class="mb-5 text-sm">La double authentification est <strong>obligatoire</strong> : après votre mot de passe, un code à 6 chiffres généré par une application de votre téléphone vous sera demandé.</p>
        @if (! $mailVerified)
            <p class="mb-3 text-sm text-muted">Par sécurité, vérifiez d'abord que vous avez accès à votre boîte mail ({{ $user->email }}).</p>
            <form method="POST" action="{{ route('security.two-factor.mail-code') }}" class="mb-4">
                @csrf
                <button class="btn-secondary w-full sm:w-auto">Recevoir un code par email</button>
            </form>
            <form method="POST" action="{{ route('security.two-factor.mail-verify') }}" class="max-w-xs">
                @csrf
                <x-form.input name="mail_code" label="Code reçu par email" inputmode="numeric" maxlength="6" autocomplete="one-time-code" />
                <button class="btn-primary w-full">Vérifier</button>
            </form>
        @else
            <form method="POST" action="{{ route('security.two-factor.enable') }}">
                @csrf
                <button class="btn-primary w-full sm:w-auto">Configurer mon application</button>
            </form>
        @endif
    @endif

    <form method="POST" action="{{ route('logout') }}" class="mt-8 border-t border-line pt-4">
        @csrf
        <button class="link text-sm">Se déconnecter</button>
    </form>
</x-layouts.guest>
