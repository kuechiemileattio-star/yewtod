{{-- Mentions légales (cahier § 8.5, bug n° 14 ; LCEN art. 6 III). Identité de l'éditeur dans .env (config/yewtod.php). --}}
@php($legal = config('yewtod.legal'))
@php($todo = 'à compléter avant la mise en ligne')
<x-layouts.public title="Mentions légales" description="Mentions légales du site {{ $siteName }} : éditeur, directeur de la publication, hébergeur.">
    <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <h1 class="text-4xl font-bold">Mentions légales</h1>
        <div class="prose-yewtod mt-8">
            <h3>Éditeur du site</h3>
            <p>{{ $siteName }} — {{ $legal['publisher'] ?: $todo }}<br>{{ $legal['publisher_address'] ?: '' }}</p>
            <p>Contact : @if ($legal['contact_email'])<a href="mailto:{{ $legal['contact_email'] }}">{{ $legal['contact_email'] }}</a>@else via la page <a href="{{ url('/collaborations') }}">Collaborations</a>@endif.</p>
            <h3>Directeur de la publication</h3>
            <p>{{ $legal['director'] ?: $todo }}</p>
            <h3>Hébergement</h3>
            <p>{{ $legal['host'] }}</p>
            <h3>Propriété intellectuelle</h3>
            <p>Les textes, données, graphiques et vidéos publiés sur ce site sont protégés par le droit d'auteur. Ils peuvent être cités dans le respect du droit de courte citation, avec mention de la source (une référence prête à copier est proposée sur chaque publication). Toute autre reproduction nécessite l'accord préalable de l'éditeur.</p>
            <h3>Liens externes</h3>
            <p>Les liens vers des sites tiers (réseaux sociaux, sources, vidéos) s'ouvrent dans une nouvelle fenêtre. L'éditeur n'est pas responsable de leur contenu.</p>
            <h3>Données personnelles</h3>
            <p>Voir la <a href="{{ route('privacy') }}">politique de confidentialité</a>.</p>
        </div>
    </article>
</x-layouts.public>
