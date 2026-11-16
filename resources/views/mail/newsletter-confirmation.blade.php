<x-mail::message>
# Plus qu'une étape

Vous avez demandé à recevoir la newsletter de **{{ config('app.name') }}**. Pour confirmer votre inscription, cliquez sur le bouton ci-dessous :

<x-mail::button :url="$url">
Confirmer mon inscription
</x-mail::button>

Ce lien est valable {{ $hours }} heures. Chaque lettre contiendra un lien de désinscription en un clic.

Si vous n'êtes pas à l'origine de cette demande, ignorez cet email : sans confirmation, votre adresse sera supprimée automatiquement sous 7 jours.

{{ config('app.name') }}
</x-mail::message>
