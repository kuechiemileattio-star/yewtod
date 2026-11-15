# Lot 02 — Rôles, permissions et écrans membres

## Ce qui a été fait

| Élément | Fichiers |
| --- | --- |
| Service `RoleAssignment` : source unique de R1, R5, R6, R8 | `app/Services/RoleAssignment.php` |
| Policies `UserPolicy`, `RolePolicy`, `InvitationPolicy` (délèguent au service) | `app/Policies/` |
| Actions sur les membres : rôle, statut, suppression, réinitialisation 2FA (Super Admin), dernier Super Admin protégé par verrou | `app/Services/UserManagement.php` |
| Rôles : création, matrice rôles × permissions, suppression (rôles système protégés) | `app/Services/RoleManagement.php` |
| Écran « Utilisateurs & rôles » (membres, invitations, rôles) | `app/Livewire/Dashboard/Users.php`, `resources/views/livewire/dashboard/users.blade.php` |
| Écran « Mon profil » (profil + photo, mot de passe, sessions actives, sécurité, apparence) | `app/Livewire/Dashboard/Profile.php` |
| Journal d'audit consultable (Super Admin) | `app/Livewire/Dashboard/AuditLog.php` |
| Gabarit du dashboard (tiroir mobile, menu filtré par permission, thème, toasts) | `resources/views/components/layouts/dashboard.blade.php`, `app/Support/DashboardMenu.php` |
| Traitement d'images (réencodage WebP, `srcset`) | `app/Services/ImageProcessor.php` |
| Revue responsive automatique (Playwright) | `tests/visual/captures.mjs`, `docs/captures/` |

## Choix expliqués

- **Une seule source de vérité** : les Policies (pour l'affichage et les `@can`) et les services (pour l'exécution) appellent tous `RoleAssignment`. Une règle n'est écrite qu'une fois ; l'écran peut mentir (bouton visible par erreur), le service refusera quand même.
- **Dernier Super Admin** : chaque rétrogradation, suspension ou suppression d'un Super Admin compte, dans une transaction avec `lockForUpdate()`, les autres Super Admins actifs. Deux administrateurs qui agiraient au même instant ne peuvent pas tous deux réussir.
- **R8 « ajouter OU retirer »** : retirer une permission qu'on ne possède pas modifierait aussi le périmètre d'un rôle qu'on ne maîtrise pas ; c'est donc refusé au même titre que l'ajout.
- **Reconfirmation du mot de passe** (15 min) pour changer un rôle, supprimer un membre, réinitialiser une 2FA : trait `ConfirmsPassword`.
- **Photo de profil** : type vérifié sur le contenu (`finfo`), puis réencodage GD en WebP 256 px. Un SVG ou un faux PNG est refusé (test). Les métadonnées EXIF disparaissent.
- **Build CSP d'Alpine** : les interactions (sélecteur de rôle, confirmations) passent par des composants `Alpine.data` (`wireCall`, `confirmAction`) au lieu d'expressions dans les gabarits.

## Menaces traitées

Escalade de privilèges (R5, R8), IDOR sur les membres (R6 vérifiée sur chaque cible), affectation de masse (R3 : `$fillable` minimal, test), verrouillage par suppression du dernier administrateur, upload malveillant (photo).

## Vérifier

```bash
php artisan test tests/Feature/Rbac tests/Feature/Dashboard
```

Connecté en `admin@yewtod.test` (données de démo), tenter de suspendre `superadmin@yewtod.test` : message « Ce membre a un rôle supérieur au vôtre ».
