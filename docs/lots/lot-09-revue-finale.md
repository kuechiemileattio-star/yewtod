# Lot 09 — Seeders, purges, sauvegardes, revue complète

## Ce qui a été fait

| Élément | Fichiers |
| --- | --- |
| Seeders de démonstration complets : 5 comptes (un par rôle), 8 types de publications (+ brouillon, relecture, programmé), 9 livres, avis dans les 3 statuts, 8 demandes de collaboration, abonnés dans les 3 statuts ; tous refusés hors local | `database/seeders/Demo*.php` |
| Purges planifiées (RGPD, rétention) | `app/Services/Retention.php`, `yewtod:purge` |
| Sauvegardes chiffrées, rotation, restauration testée | `app/Services/Backup.php`, `yewtod:backup`, `yewtod:backup:restore` |
| Revue de couverture : matrice R1–R8, mot de passe, § 8, bugs 1–17 ; tests d'architecture (sorties non échappées, `$fillable`, SQL interpolé, sessions, commandes destructrices, seeders, code mort) | `docs/SECURITE.md` § 6, `tests/Feature/Socle/HardeningTest.php` |
| Accessibilité : audit axe-core WCAG 2.1 AA sur toutes les pages en clair et en sombre → 0 violation sérieuse ou critique (corrections : nom accessible du bouton de recherche, structure du sommaire, contrastes des pastilles et du sommaire, attribut `class` dupliqué) | `tests/visual/a11y.mjs`, `docs/captures/accessibilite.md` |
| Revue responsive complète : toutes les pages, 5 largeurs, clair et sombre, sans débordement | `docs/captures/rapport.md` |
| Performance : cache des réglages et du sitemap/RSS, index sur toutes les colonnes filtrées, WebP + srcset, cache long des assets, compression | `public/.htaccess`, services |
| Sécurité : `docs/SECURITE.md` ; mise à jour de pdf.js (vulnérabilité haute) ; `composer audit` et `npm audit` propres | |

## Vérifier

```bash
php artisan test                                   # toute la suite
php artisan yewtod:backup && php artisan yewtod:purge
COOKIE="$(php artisan yewtod:dev-session admin@yewtod.test)" node tests/visual/a11y.mjs
COOKIE="$(php artisan yewtod:dev-session admin@yewtod.test)" node tests/visual/captures.mjs
```
