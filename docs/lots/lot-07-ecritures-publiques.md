# Lot 07 — Écritures publiques

## Ce qui a été fait

| Élément | Fichiers |
| --- | --- |
| Tables `reviews` (polymorphe), `collaborations`, `collaboration_files`, `newsletter_subscribers` avec CHECK sur statuts, types et longueurs | `database/migrations/2026_11_16_100300_create_public_writes_tables.php` |
| Avis sous les articles et les livres : toujours « en attente », IP hachée, approuvés seuls visibles, texte immuable | `app/Services/ReviewService.php`, `app/Models/Review.php`, `partials/public/reviews.blade.php` |
| Écran Modération (avis selon `manage_articles` / `manage_books`, qui/quand tracés) | `app/Livewire/Dashboard/Moderation.php` |
| Formulaire Collaborations (8 types, ≤ 3 pièces jointes vérifiées sur le contenu, 10 Mo, disque privé), description chiffrée | `app/Services/CollaborationService.php`, `resources/views/site/collaborations.blade.php` |
| Écran Collaborations : 3 tuiles, tableau / cartes mobiles, fiche, statut, pièces jointes par lien signé 10 min, export CSV sûr | `app/Livewire/Dashboard/Collaborations.php`, `app/Http/Controllers/Dashboard/CollaborationFileController.php`, `app/Support/SafeCsv.php` |
| Newsletter : double opt-in 48 h, réponse identique, désinscription en un clic, réinscription non silencieuse, synchronisation Brevo en file (désactivée sans clé) | `app/Services/NewsletterService.php`, `app/Jobs/SyncNewsletterWithBrevo.php` |
| Anti-spam : champ piège, Turnstile côté serveur (désactivé sans clé), limitation par IP sur les 4 formulaires | `app/Services/SpamGuard.php`, `app/Services/Turnstile.php`, `AppServiceProvider` |
| Pastilles du menu : avis en attente, nouvelles collaborations | `AppServiceProvider::registerPublicSources()` |
| Purges quotidiennes : demandes > 24 mois, inscriptions non confirmées > 7 jours | `yewtod:purge` (`routes/console.php`) |

## Menaces traitées

Spam et robots (honeypot, Turnstile, limitation), énumération des abonnés (réponse et comportement identiques), affectation de masse (statuts et dates imposés), XSS (avis échappés), upload malveillant (contenu réel vérifié, pas de SVG, disque privé), fuite de données personnelles (description chiffrée, liens signés 10 min + permission), injection CSV (bug n° 11), IDOR en modération (file filtrée par permission avant la recherche par identifiant).

## Vérifier

```bash
php artisan test tests/Feature/PublicWrites   # 32 tests
php artisan yewtod:purge
```
