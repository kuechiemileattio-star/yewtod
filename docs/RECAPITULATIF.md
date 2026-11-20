# Récapitulatif de livraison — refonte Laravel Yewtod SS

Branche `v2`, 11 commits (lots 00 à 10), tous au nom de Yann OLOU, sans mention d'assistant (décision 15).

## 1. Ce qui est fait, lot par lot

| Lot | Contenu | Détail |
| --- | --- | --- |
| 00 | Socle Laravel 13, deux connexions MariaDB (app / migrations) avec repli sur l'utilisateur unique LWS, en-têtes de sécurité, installation WSL | `docs/lots/lot-00-socle.md` |
| 01 | Fortify, invitations hachées 7 jours, onboarding, 2FA obligatoire + codes de secours, politique de mot de passe, réinitialisation, sessions, limitation, audit | `docs/lots/lot-01-authentification.md` |
| 02 | Rôles et permissions, R1–R8, écrans Utilisateurs & rôles, Mon profil, journal d'audit | `docs/lots/lot-02-rbac.md` |
| 03 | Système de design par jetons, mode sombre sans flash (public et dashboard), composants accessibles | `docs/lots/lot-03-design-system.md` |
| 04 | Contenus unifiés (8 types), éditeur, circuit éditorial, programmation, envois sécurisés, pdf.js (analyse IA retirée ensuite, D71) | `docs/lots/lot-04-contenus.md` |
| 05 | Site public : accueil, catalogue filtrable, 3 gabarits de détail, graphiques SVG, vidéos, téléchargements comptés, aperçu signé, Meet, pages légales, 404, SEO, sitemap, RSS, Ctrl K | `docs/lots/lot-05-site-public.md` |
| 06 | Livres : prix EUR, fiche 100 % française, ebook et extraits privés, éditeur modal, signets PDF | `docs/lots/lot-06-livres.md` |
| 07 | Avis pré-modérés et modération, collaborations chiffrées + pièces jointes privées + export CSV sûr, newsletter double opt-in + Brevo, Turnstile, honeypot, limitation par IP | `docs/lots/lot-07-ecritures-publiques.md` |
| 08 | Paramètres, pages éditables, médiathèque, newsletter, vue d'ensemble | `docs/lots/lot-08-administration.md` |
| 09 | Seeders de démo complets, purges, sauvegardes chiffrées et restauration testée, tests d'architecture, audit d'accessibilité, `SECURITE.md` | `docs/lots/lot-09-revue-finale.md` |
| 10 | Scripts de déploiement / retour arrière / copie des sauvegardes, préproduction protégée, vérification de production, `DEPLOIEMENT.md` | `docs/lots/lot-10-deploiement.md` |

## 2. Résultats des vérifications

- **Tests Pest : 328, tous verts (1 157 assertions ; après le retrait de l'analyse IA, D71, et l'ajout du webmail, D72)**, sur MariaDB 10.11 réelle (base `yewtod_test`), après `php artisan migrate:fresh --seed`.
- **Pint** propre, **Larastan niveau 6** sans erreur.
- **Responsive** : 36 écrans × 5 largeurs (320, 375, 768, 1024, 1440) × 2 thèmes = 360 captures, aucun défilement horizontal (`docs/captures/rapport.md`).
- **Accessibilité** : axe-core WCAG 2.1 A/AA sur tous ces écrans, clair et sombre : 0 violation sérieuse ou critique (`docs/captures/accessibilite.md`).
- **Dépendances** : `composer audit` et `npm audit` sans vulnérabilité (pdf.js mis à jour en 6.5).
- **Sauvegarde / restauration** : restauration complète testée dans une base vide (comptes de lignes et fichiers identiques).
- **Audit de sécurité dynamique** (ZAP, sqlmap, nuclei, nikto, sondes manuelles sur Apache et en mode production) : 1 vulnérabilité haute (empoisonnement par l'en-tête Host), 1 moyenne (CSP des styles) et 4 faibles **corrigées** ; aucune injection SQL, aucun XSS exécutable, aucune escalade de privilèges (`docs/AUDIT-SECURITE.md`).

## 3. Ce qui n'a pas pu être vérifié ici

- Le **déploiement réel chez LWS** (aucun accès à l'hébergement depuis l'environnement de génération) : scripts relus et syntaxe vérifiée (`bash -n`), à exécuter en préproduction.
- Les **services tiers avec de vraies clés** : Brevo, Turnstile et SMTP sont testés avec des réponses simulées et en mode « désactivé » ; les appels réels sont à valider avec vos clés de test.
- **Have I Been Pwned** en conditions réelles (API injoignable depuis l'environnement de génération ; testé par simulation, D10).
- La **version exacte de MariaDB chez LWS** : le projet est testé sur MariaDB 10.11 ; à confirmer avec `SELECT VERSION();`.
- `composer.lock` : généré ici mais **non versionné** (D3) car une dépendance de développement (phpstan) a dû être installée depuis un dossier local ; à régénérer chez vous.
- L'**affichage sur de vrais appareils** (captures faites dans Chrome sans tête) et la lecture avec un vrai lecteur d'écran.

## 4. Conformité au cahier des charges

| Section | Statut | Où |
| --- | --- | --- |
| 1 Vue d'ensemble, acteurs | Couvert | Rôles, site public, dashboard |
| 2.1 Mise en page commune | Couvert | `partials/public/nav|footer`, composants `siteNav`, `reveal`, `backToTop` |
| 2.2 Accueil | Couvert | `HomeController`, `PublicSiteTest` (bloc accueil) |
| 2.3 Publications | Couvert (Pays/Langue « bientôt disponible » comme demandé) | `WorksQuery`, `PublicSiteTest` (bloc catalogue) |
| 2.4 Détail (3 gabarits) | Couvert | `site/works/templates/*`, `PublicSiteTest` (bloc page détail) |
| 2.5 Meet | Couvert | `site/meet.blade.php`, `AdministrationTest` |
| 2.6 Livres | Couvert | `BookController`, `BooksTest` |
| 2.7 Collaborations | Couvert | `CollaborationService`, `PublicWritesTest` |
| 2.8 Écrits publics | Couvert | `ReviewService`, `NewsletterService`, `PublicWritesTest` |
| 3 Contenus (8 types, vidéos, graphiques) | Couvert | `ContentRules`, `SvgChart`, `VideoEmbed`, `EditorTest`, `PublicSiteTest` |
| 3.1 Livres (champs) | Couvert | migration `books`, `BookService::rules()` |
| 3.2 Modèle unifié | Couvert | table `contents`, `ContentWorkflow` |
| 4 Menu par permission | Couvert | `DashboardMenu`, `AccessAndProfileTest` |
| 4.1 Vue d'ensemble | Couvert | `OverviewController`, `AdministrationTest` |
| 4.2 Listes de contenus | Couvert | `ContentIndex`, `EditorTest` |
| 4.3 Éditeur | Couvert (texte riche minimal, D25) | `ContentEditor`, `EditorTest`, `UploadsTest` |
| 4.4 Livres (dashboard) | Couvert | `Books` (Livewire), `BooksTest` |
| 4.5 Collaborations (dashboard) | Couvert | `Collaborations` (Livewire), `PublicWritesTest` |
| 4.6 Modération | Couvert | `Moderation` (Livewire), `PublicWritesTest` |
| 4.7 Utilisateurs & rôles | Couvert | `Users` (Livewire), `RulesTest` |
| 4.8 Pages | Couvert | `Pages` (Livewire), `AdministrationTest` |
| 4.9 Paramètres | Couvert | `Settings` (Livewire), `AdministrationTest` |
| 4.10 Mon profil | Couvert | `Profile` (Livewire), `AccessAndProfileTest` |
| 5.1 Publication et visibilité | Couvert | `published()`, `yewtod:publish-scheduled`, `WorkflowTest` |
| 5.2 Affichage calculé | Couvert | `Content::displayAuthor()/excerpt()/citation()`, `WorkflowTest` |
| 5.3 Statistiques | Couvert | `EventCounter`, `PublicSiteTest` (statistiques) |
| 5.4 Avis | Couvert | `ReviewService`, `PublicWritesTest` |
| 5.5 Collaborations | Couvert | `CollaborationService`, `PublicWritesTest` |
| 5.6 Newsletter | Couvert | `NewsletterService`, `PublicWritesTest` |
| 5.7 Comptes, invitations | Couvert | `InvitationService`, `InvitationOnboardingTest` |
| 5.8 Analyse PDF | Partiel (choix) | `pdf-analyse.js` (pages, signets) ; analyse IA retirée à la demande du propriétaire (D71) |
| 5.9 Contenus éditables | Couvert | `SiteAdmin`, `AdministrationTest` |
| 6 Rôles, R1–R8 | Couvert | `RoleAssignment`, `RulesTest` (matrice dans `SECURITE.md` § 6) |
| 7 Modèle de données | Couvert (pas de reprise, décision 4) | migrations |
| 8.1 2FA | Couvert | `TwoFactorMandatoryTest`, `LoginTest` |
| 8.2 Mot de passe | Couvert | `PasswordPolicyTest` |
| 8.3 Connexion et sessions | Couvert | `LoginTest`, `PasswordResetTest`, `HardeningTest` |
| 8.4 Exigences de l'audit | Couvert | `SECURITE.md` § 1 |
| 8.5 Traçabilité et RGPD | Couvert | audit, pages légales, `yewtod:purge`, sauvegardes |
| 8.6 Base de données | Couvert | `SECURITE.md` § 2, `DatabaseConfigTest`, `HardeningTest` |
| 9 Mode sombre | Couvert | jetons CSS, `DesignSystemTest`, `ContrastTest`, captures |
| 10 P1 (SEO, 404, livres FR, pagination/URL, a11y, toasts, pastilles, non-enregistré, aperçu réel) | Couvert | lots 03–08 |
| 10 P2 (éditeur riche, circuit éditorial, médiathèque, newsletter, pages étendues, listes, Ctrl K, WebP, APA/BibTeX + impression) | Couvert ; médiathèque **partielle** : réutilisation par URL copiée, pas d'insertion directe dans le corps (D52) | `MediaLibrary`, `SearchController`, … |
| 10 P3 (avis sur d'autres types, Pays/Langue, raccourcis N et /) | **Non couvert** (prévu « plus tard » ; décision 6 limite les avis aux articles et livres) | — |
| 11 Bugs 1–17 | Couvert (un test par bug) | `SECURITE.md` § 6 |
| 12.1–12.2 Socle et organisation | Couvert (sans `spatie/laravel-sitemap`, D33) | — |
| 12.3 Environnements et déploiement | Couvert (scripts et procédure) ; **exécution chez LWS à faire** | `DEPLOIEMENT.md`, `scripts/` |
| 12.4 Données de démonstration | Couvert | `database/seeders/Demo*.php` |
| 13 Décisions 1–15 et 13.1 | Couvert | `DECISIONS.md` (D1–D65 appliquent ces décisions) |
