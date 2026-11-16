{{-- Page détail d'une publication (cahier § 2.4) : un des 3 gabarits selon le type. --}}
<x-layouts.public :title="$content->seoTitleText()" :description="$content->seoDescriptionText()"
    :canonical="$content->publicUrl()" :image="$content->coverUrl()" og-type="article"
    :json-ld="$preview ? null : $content->jsonLd()" :noindex="$preview">
    @if ($preview)
        <div class="sticky top-16 z-30 border-b border-warning bg-warning/90 px-4 py-2 text-center text-sm font-semibold text-navy" role="status">
            Aperçu réel — ce contenu n'est pas visible du public (statut : {{ $content->status->label() }}). Ce lien expire dans 30 minutes.
        </div>
    @endif
    @include('site.works.templates.'.$content->type->template())
</x-layouts.public>
