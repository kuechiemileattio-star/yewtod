<x-mail::message>
# Bonjour,

{{ $inviterName }} vous invite à rejoindre l'espace membres de **{{ config('app.name') }}** avec le rôle **{{ $role }}**.

<x-mail::button :url="$url">
Activer mon compte
</x-mail::button>

Ce lien est personnel et utilisable une seule fois. Il expire le {{ $expires }}.

Lors de l'activation, vous choisirez un mot de passe puis configurerez la double authentification (application Google Authenticator, Microsoft Authenticator, 2FAS, Bitwarden…).

Si vous n'attendiez pas cette invitation, ignorez simplement cet email.

{{ config('app.name') }}
</x-mail::message>
