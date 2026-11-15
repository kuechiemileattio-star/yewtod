<x-layouts.guest title="Double authentification">
    <div x-data="toggle">
        <p class="mb-5 text-sm text-muted" x-show="!on">Saisissez le code à 6 chiffres affiché par votre application d'authentification.</p>
        <p class="mb-5 text-sm text-muted" x-show="on" x-cloak>Saisissez l'un de vos codes de secours. Chaque code ne fonctionne qu'une fois.</p>
        <form method="POST" action="{{ route('two-factor.login.store') }}">
            @csrf
            <div x-show="!on">
                <x-form.input name="code" label="Code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" maxlength="6" autofocus />
            </div>
            <div x-show="on" x-cloak>
                <x-form.input name="recovery_code" label="Code de secours" autocomplete="off" maxlength="40" />
            </div>
            <button type="submit" class="btn-primary w-full">Valider</button>
        </form>
        <button type="button" class="btn-ghost mt-3 w-full" x-on:click="flip">
            <span x-show="!on">Utiliser un code de secours</span>
            <span x-show="on" x-cloak>Utiliser l'application d'authentification</span>
        </button>
    </div>
</x-layouts.guest>
