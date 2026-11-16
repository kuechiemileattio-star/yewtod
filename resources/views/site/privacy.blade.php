{{-- Politique de confidentialité (cahier § 8.5, RGPD). Les durées viennent de config/yewtod.php. --}}
@php($r = config('yewtod.retention'))
<x-layouts.public title="Politique de confidentialité" description="Quelles données {{ $siteName }} collecte, pourquoi, combien de temps et comment exercer vos droits.">
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <h1 class="text-4xl font-bold">Politique de confidentialité</h1>
        <p class="mt-4 text-muted">Nous collectons le strict nécessaire, pour une durée limitée, et nous ne vendons ni ne partageons vos données à des fins publicitaires.</p>
        <div class="prose-yewtod mt-8">
            <h3>Aucun cookie de suivi</h3>
            <p>Ce site n'utilise aucun outil de mesure d'audience tiers ni cookie publicitaire : aucun bandeau de consentement n'est donc nécessaire. Deux cookies techniques peuvent être déposés : le cookie de session (sécurité des formulaires) et un cookie <code>theme</code> qui mémorise votre choix clair/sombre.</p>
            <h3>Statistiques de consultation</h3>
            <p>Les vues et téléchargements sont comptés de façon anonyme : nous n'enregistrons ni votre adresse IP ni votre identité, seulement la date et la publication concernée (conservées {{ $r['content_events_months'] }} mois).</p>
            <h3>Avis des lecteurs</h3>
            <p>Nom (ou pseudonyme) et texte de l'avis, publiés après relecture. L'adresse IP est conservée sous forme hachée (irréversible), uniquement pour lutter contre les abus.</p>
            <h3>Newsletter</h3>
            <p>Adresse email, après double confirmation. Les inscriptions non confirmées sont supprimées après {{ $r['newsletter_pending_days'] }} jours. Chaque lettre contient un lien de désinscription en un clic. L'envoi est confié à Brevo (Sendinblue SAS, France), qui héberge les données dans l'Union européenne.</p>
            <h3>Demandes de collaboration</h3>
            <p>Nom, organisation, email, sujet, description et pièces jointes éventuelles. La description est chiffrée en base, les pièces jointes sont stockées hors de l'espace public. Ces demandes sont supprimées automatiquement après {{ $r['collaborations_months'] }} mois.</p>
            <h3>Services tiers</h3>
            <p>Les vidéos YouTube sont intégrées en mode « confidentialité renforcée » (youtube-nocookie.com) : aucun cookie YouTube n'est déposé avant que vous ne lanciez la lecture. La protection anti-robots Cloudflare Turnstile peut être utilisée sur les formulaires.</p>
            <h3>Vos droits</h3>
            <p>Vous disposez d'un droit d'accès, de rectification, d'effacement, d'opposition et de limitation. Pour l'exercer, écrivez-nous via la page <a href="{{ url('/collaborations') }}">Collaborations</a>@if (config('yewtod.legal.contact_email')) ou à <a href="mailto:{{ config('yewtod.legal.contact_email') }}">{{ config('yewtod.legal.contact_email') }}</a>@endif. Vous pouvez aussi saisir la CNIL (<a href="https://www.cnil.fr" target="_blank" rel="noopener noreferrer">cnil.fr</a>).</p>
        </div>
    </article>
</x-layouts.public>
