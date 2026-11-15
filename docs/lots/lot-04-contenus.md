# Lot 04 — Contenus unifiés, éditeur, circuit éditorial

## Ce qui a été fait

| Élément | Fichiers |
| --- | --- |
| Table `contents` unique (8 types), `sections`/`meta`/`references`/`tags`/`themes` en JSON, contraintes CHECK (type, statut, épisode, programmation), index uniques (slug par type, numéro d'épisode par série) | `database/migrations/2026_11_15_100700_create_contents_tables.php` |
| Contenus similaires par identifiant (`content_related`), analyses IA (`ai_analyses`) | même migration |
| Modèle `Content` : scope `published()`, auteur affiché, extrait, citation (APA, BibTeX), couverture par défaut stable | `app/Models/Content.php` |
| Règles de validation par type (meta) | `app/Services/ContentRules.php` |
| Circuit éditorial : brouillon → relecture → publication / programmation ; slug unique ; numérotation d'épisode sous verrou ; temps de lecture sur toutes les sections ; bascule des programmés | `app/Services/ContentWorkflow.php`, `routes/console.php` |
| Nettoyage HTML (HTML Purifier, liste blanche) | `app/Services/HtmlSanitizer.php` |
| Liens http/https uniquement | `app/Rules/HttpUrl.php` |
| Envois : couverture (WebP), PDF privé avec contrôle `%PDF-`, CSV (guillemets, retours à la ligne), fichiers téléchargeables | `app/Services/UploadService.php` |
| Analyse IA Gemini en file d'attente, réponse filtrée, 429 = quota | `app/Services/GeminiAnalyzer.php`, `app/Jobs/AnalyzePdfWithAi.php` |
| Analyse PDF dans le navigateur (pdf.js, sans eval) | `resources/js/pdf-analyse.js` |
| Listes du dashboard (cartes, filtres, recherche, tri, pagination, actions groupées, aperçu modal) | `app/Livewire/Dashboard/ContentIndex.php` |
| Éditeur (5 sections numérotées, aperçu en direct, constructeurs de blocs, texte riche, sauvegarde auto, alerte « non enregistré », envoi bloquant l'enregistrement) | `app/Livewire/Dashboard/ContentEditor.php` |

## Choix expliqués

- **Une seule table, des règles par type** : la base garantit la structure commune et les invariants critiques (CHECK) ; la forme de `meta` est validée par `ContentRules`, et toute clé inconnue est jetée avant l'enregistrement. On ne peut donc pas « glisser » un champ arbitraire dans le JSON.
- **Circuit éditorial (décision 10)** : la permission `publish_content` sépare l'Éditeur du Contributeur. Le Contributeur ne modifie que ses propres brouillons ou relectures (bug n° 5).
- **Épisodes (bug n° 1)** : le numéro est proposé (dernier + 1) dans une transaction qui verrouille la série et ses épisodes ; deux créations simultanées ne peuvent pas obtenir le même numéro (et l'index unique le garantit en dernier recours).
- **Documents sur disque privé** : un PDF de brouillon n'est jamais accessible par URL ; le lot 05 le sert par un contrôleur qui vérifie la visibilité et compte le téléchargement.
- **IA sans SSRF** : l'analyse ne prend qu'un chemin de fichier déjà stocké ; aucune URL n'est jamais téléchargée par le serveur. Le PDF est contrôlé (taille, signature) avant lecture ; la consigne système demande d'ignorer les instructions du document ; la réponse est bornée.
- **Texte riche** : éditeur `contenteditable` léger (pas de dépendance lourde) ; ce qui compte pour la sécurité est le nettoyage serveur, appliqué quoi qu'envoie le navigateur.

## Vérifier

```bash
php artisan test tests/Feature/Contents
php artisan yewtod:publish-scheduled
```
