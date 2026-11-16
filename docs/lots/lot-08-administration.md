# Lot 08 — Administration

## Ce qui a été fait

| Élément | Fichiers |
| --- | --- |
| Paramètres : nom du site, description SEO, logo, favicon (bug n° 2), réflexion de la semaine (date automatique), réseaux sociaux ordonnés (https) | `app/Services/SiteAdmin.php`, `app/Livewire/Dashboard/Settings.php` |
| Pages éditables : Meet (accroche, 3 cartes, FAQ, fondateur, portrait, bio, expertises), Accueil (accroche, piliers), pied de page (bug n° 7) | `SiteAdmin::savePage()`, `app/Livewire/Dashboard/Pages.php` |
| Médiathèque (bug n° 6) : envoi 20 Mo, WebP + srcset, PDF, texte alternatif, URL à copier, détection et suppression des orphelins | `app/Services/MediaLibrary.php`, `app/Livewire/Dashboard/MediaManager.php`, migration `media` |
| Newsletter : abonnés, compteurs, recherche, filtre, export CSV sûr, suppression RGPD (et retrait Brevo), alerte si Brevo non configuré | `app/Livewire/Dashboard/Newsletter.php`, `NewsletterExportController` |
| Vue d'ensemble : 4 tuiles « +N ce mois », publications par mois sur 12 mois, top 3 des 7 derniers jours, 6 contenus récents | `app/Http/Controllers/Dashboard/OverviewController.php`, `resources/views/dashboard/home.blade.php` |

## Choix expliqués

- Chaque écran est protégé deux fois : middleware `permission:` sur la route, puis contrôle dans le service (un appel Livewire forgé ne contourne rien).
- Les réglages vident le cache de `SiteSettings` : le site public change immédiatement.
- Les statistiques utilisent le même scope `published()` que le site : un programmé échu compte comme publié (bug n° 16).

## Vérifier

```bash
php artisan test tests/Feature/Admin   # 15 tests
```
