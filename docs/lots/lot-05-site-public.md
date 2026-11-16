# Lot 05 — Site public

## Ce qui a été fait

| Élément | Fichiers |
| --- | --- |
| Accueil : accroche éditable, « À lire maintenant », 3 piliers, à la une (dernière publication, dernier rapport, dernière série), réflexion de la semaine (masquée si vide), 4 « mis en avant » pilotés par `is_featured` (bug n° 4), 6 dernières publications, état vide | `app/Http/Controllers/Site/HomeController.php`, `resources/views/site/home.blade.php` |
| Catalogue `/works` : pastilles par type avec compteurs, recherche (titre, extrait, tags), axe, types, série, auteur, période, tri, pagination (12), « N résultats sur M », filtres Pays/Langue « bientôt disponible », filtres dans l'URL, tiroir de filtres sur mobile | `app/Services/WorksQuery.php`, `resources/views/site/works/index.blade.php` |
| Page détail : 3 gabarits (article, revue scientifique, documentaire), sommaire interactif, partage X/LinkedIn/Facebook + partage natif, citation APA + BibTeX, publications associées, « À lire aussi », champs propres à chaque type (bug n° 8), références | `app/Http/Controllers/Site/WorkController.php`, `resources/views/site/works/**` |
| Graphiques SVG rendus côté serveur (6 types), tableau de données accessible | `app/Support/Charts/SvgChart.php` |
| Vidéos YouTube nocookie arrêtées à 2 min + « Continuer sur YouTube », Vimeo, MP4/WebM | `app/Support/VideoEmbed.php`, `partials/video.blade.php` |
| Téléchargement réel (`Content-Disposition: attachment`) depuis le disque privé, visibilité vérifiée, comptage, limitation par IP | `WorkController::download()`, `::file()` |
| Comptage des vues/téléchargements (table `content_events`) | `app/Services/EventCounter.php`, migration `2026_11_16_100100` |
| Aperçu réel signé des brouillons (bouton « Aperçu réel » de l'éditeur) | route `content.preview` |
| Meet, Mentions légales, Confidentialité, vraie 404 (recherche + dernières publications) | `PageController`, `resources/views/site/*.blade.php`, `errors/404.blade.php` |
| SEO serveur : titre, description, canonique, Open Graph, Twitter, JSON-LD (WebSite, Article, ScholarlyArticle), `sitemap.xml`, flux RSS `/feed`, `robots.txt` | `SeoController`, `Content::jsonLd()` |
| Recherche globale Ctrl K (JSON, contenus visibles uniquement) | `SearchController`, `partials/public/search.blade.php`, `globalSearch` |
| Publications de démonstration (8 types, brouillon, relecture, programmé) | `database/seeders/DemoContentSeeder.php` |

## Choix expliqués

- **Une seule porte d'entrée publique** : toute lecture passe par `Content::published()`. Un brouillon renvoie 404 (et non 403, qui confirmerait son existence), y compris pour son PDF.
- **Filtres en liste blanche** (cahier § 8.6) : chaque paramètre d'URL est comparé au registre des types, aux axes ou aux tris connus, ou converti en entier/date valide ; le reste est ignoré. La recherche n'utilise que des requêtes liées et échappe les jokers `%` et `_` (sinon `q=%` listerait tout).
- **HTML des sections** : c'est la seule sortie non échappée du site ; elle est nettoyée par HTML Purifier deux fois (D27). Les textes bruts sont structurés puis échappés.
- **Graphiques côté serveur** : pas de bibliothèque JavaScript, rendu visible sans JS, chaque libellé échappé ; l'infobulle native (`<title>`) affiche les valeurs.
- **404 dans le groupe web** : `Route::fallback()` fait passer les URL inconnues par les middlewares (CSP, session, thème) au lieu d'un rendu brut d'exception.
- **Compteurs indicatifs** : une fois par session, plafond par IP hachée, rien d'identifiant en base (D34).

## Menaces traitées

Injection SQL (filtres, tri, recherche), XSS (titres, sections, libellés de graphiques, liens `javascript:`), fuite de brouillons (pages, PDF, sitemap, RSS, recherche), traversée de chemin (fichiers d'expérimentation), gonflement des compteurs et DoS (limitation par IP, pagination bornée, cache sitemap/RSS).

## Vérifier

```bash
php artisan test tests/Feature/Site      # 56 tests
php artisan db:seed --class=DemoSeeder   # données de démo
node tests/visual/captures.mjs accueil   # captures responsive
```
