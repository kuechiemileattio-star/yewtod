{{-- Confirmation et désinscription newsletter (cahier § 13.1). --}}
<x-layouts.public title="Newsletter" :noindex="true">
    <section class="mx-auto max-w-xl px-4 py-20 text-center sm:px-6">
        @switch($state)
            @case('confirmed')
                <h1 class="text-3xl font-bold">Inscription confirmée</h1>
                <p class="mt-3 text-muted">Merci ! Vous recevrez la prochaine lettre. Vous pourrez vous désinscrire en un clic depuis chaque envoi.</p>
                <p class="mt-6 text-sm"><a class="link" href="{{ route('newsletter.unsubscribe', $token) }}">Changer d'avis : me désinscrire</a></p>
                @break
            @case('unsubscribe')
                <h1 class="text-3xl font-bold">Se désinscrire</h1>
                <p class="mt-3 text-muted">Vous ne recevrez plus la newsletter.</p>
                <form method="POST" action="{{ route('newsletter.unsubscribe.confirm', $token) }}" class="mt-6">
                    @csrf
                    <button class="btn-primary">Confirmer la désinscription</button>
                </form>
                @break
            @case('unsubscribed')
                <h1 class="text-3xl font-bold">Vous êtes désinscrit</h1>
                <p class="mt-3 text-muted">Votre adresse ne recevra plus la newsletter. Vous pourrez vous réinscrire à tout moment (une nouvelle confirmation vous sera demandée).</p>
                @break
            @default
                <h1 class="text-3xl font-bold">Lien invalide ou expiré</h1>
                <p class="mt-3 text-muted">Ce lien n'est plus valable (il expire après 48 heures). Vous pouvez recommencer l'inscription depuis le pied de page.</p>
        @endswitch
        <a href="{{ url('/') }}" class="btn-secondary mt-8">Retour à l'accueil</a>
    </section>
</x-layouts.public>
