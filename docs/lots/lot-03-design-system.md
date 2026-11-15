# Lot 03 — Système de design et mode sombre

## Ce qui a été fait

| Élément | Fichiers |
| --- | --- |
| Jetons sémantiques clair/sombre (`page`, `surface`, `ink`, `muted`, `line`, `line-strong`, `accent`, `accent-ink`, `accent-2`, `accent-2-ink`, `warning`, `danger`, `success`, `focus`) | `resources/css/app.css` |
| Classes utilitaires de composants (`btn-*`, `field-*`, `card`, `badge`, `link`, `skip-link`) | `resources/css/app.css` |
| Composants Blade : `icon`, `status-badge`, `empty-state`, `page-header`, `modal`, `confirm-dialog`, `confirm-action`, `tabs`, `toasts`, `theme-switcher`, `form.input`, `form.password` | `resources/views/components/` |
| Gabarit public : navigation (indicateur animé, progression de lecture, ombre, menu plein écran qui bloque le défilement), pied de page (appel « Démarrer une conversation », liens par type, réseaux sociaux, retour en haut), SEO serveur | `resources/views/components/layouts/public.blade.php`, `resources/views/partials/public/` |
| Gabarit dashboard (déjà au lot 02) : tiroir mobile, bascule de thème enregistrée sur le compte | `resources/views/components/layouts/dashboard.blade.php` |
| Réglages, pages éditables et réseaux sociaux lus côté serveur avec cache (bug n° 2 et n° 7) | `app/Services/SiteSettings.php`, `app/Services/PageDefaults.php`, migration `2026_11_15_100600` |
| Registre des 8 types de publications | `app/Enums/ContentType.php` |
| Guide de style vivant | `/dashboard/design` |
| Test automatique des contrastes WCAG AA dans les deux modes | `tests/Unit/ContrastTest.php` |

## Choix expliqués

- **Pas de flash blanc** : la classe `dark` est posée par le serveur sur `<html>` (préférence du compte, sinon cookie). En mode « Système », un script de 1 ligne autorisé par le nonce CSP applique le thème avant le premier affichage.
- **Jetons nommés par rôle** : l'ancien piège (`green` = orange) disparaît. Les accents « purs » sont décoratifs ; les variantes `-ink` portent le texte et passent 4,5:1 (vérifié par le test de contraste, qui lit directement `app.css`).
- **Bordures de champs** : la couleur `line` (#CAD5DF) n'atteignait que 1,4:1 sur blanc, insuffisant pour délimiter un champ (WCAG 1.4.11). Les champs utilisent `line-strong` (≥ 3:1).
- **Cookie de thème non chiffré** : il est écrit par le navigateur et ne peut prendre que trois valeurs, revalidées côté serveur (test avec une valeur malveillante).
- **Cache sûr** : Laravel 13 refuse de désérialiser des objets depuis le cache ; on y met des tableaux simples (test sur le cache en base, comme en production).
- **Accessibilité** : lien d'évitement, focus toujours visible (3 px, couleur `focus`), cibles de 44 px, `aria-current`, `prefers-reduced-motion` respecté (animations coupées, apparition au défilement désactivée).

## Vérifier

- `/dashboard/design` : tous les composants en clair puis en sombre (bouton de thème en haut à droite).
- `php artisan test tests/Unit/ContrastTest.php tests/Feature/Socle/DesignSystemTest.php`
- `docs/captures/rapport.md` : aucun défilement horizontal de 320 à 1440 px.
