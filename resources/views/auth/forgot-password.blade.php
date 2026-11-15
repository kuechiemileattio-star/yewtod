<x-layouts.guest title="Mot de passe oublié">
    <p class="mb-5 text-sm text-muted">Indiquez votre adresse email. Si elle correspond à un compte, vous recevrez un lien valable 60 minutes.</p>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-form.input name="email" type="email" label="Adresse email" autocomplete="username" required autofocus maxlength="254" />
        <button type="submit" class="btn-primary w-full">Envoyer le lien</button>
    </form>
    <p class="mt-5 text-center text-sm"><a class="link" href="{{ route('login') }}">Retour à la connexion</a></p>
</x-layouts.guest>
