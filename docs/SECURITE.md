# Sécurité — Yewtod SS

> Voir aussi `docs/AUDIT-SECURITE.md` : audit dynamique (ZAP, sqlmap, nuclei, nikto, sondes manuelles) du 16/11/2026, vulnérabilités trouvées et corrigées.

Ce document relie chaque menace à sa protection, au fichier qui l'implémente et au test qui la prouve (cahier § 8, § 8.6, prompt § 3). Les tests sont dans `tests/` ; `php artisan test` les rejoue tous (MariaDB réelle, base `yewtod_test`).

## 1. Menaces, protections et preuves

| Menace | Protection | Fichier(s) | Test(s) |
| --- | --- | --- | --- |
| **Injection SQL** | Eloquent et Query Builder avec paramètres liés uniquement ; filtres, tris et types confrontés à des listes blanches ; jokers `LIKE` échappés ; aucun SQL brut interpolé (vérifié automatiquement) | `app/Services/WorksQuery.php`, `BookController::index()`, listes Livewire | `PublicSiteTest` « résiste aux tentatives d'injection SQL » (11 variantes), « ne renvoie rien pour une injection dans la recherche » ; `BooksTest` « filtre … en liste blanche » ; `HardeningTest` « aucune variable interpolée dans du SQL brut » |
| **XSS stocké / réfléchi** | Échappement Blade `{{ }}` partout ; `{!! !!}` limité à une liste fermée de sorties nettoyées ou générées par le serveur ; HTML riche passé par HTML Purifier à l'enregistrement **et** à l'affichage ; SVG des graphiques construits avec échappement ; CSP sans `unsafe-inline` pour les scripts **et les styles** (nonce) | `app/Services/HtmlSanitizer.php`, `app/Support/TextStructurer.php`, `app/Support/Charts/SvgChart.php`, `app/Http/Middleware/SecurityHeaders.php` | `WorkflowTest` « nettoie le HTML des sections » ; `PublicSiteTest` « échappe le titre et nettoie le HTML », « graphiques … données échappées » ; `PublicWritesTest` « échappe le texte d'un avis » ; `HardeningTest` « {!! !!} … liste fermée » ; `SecurityHeadersTest` |
| **Liens malveillants** (`javascript:`, `data:`) | Règle `HttpUrl` (http/https seulement) sur toute URL saisie, revérifiée à l'affichage ; `rel="noopener noreferrer"` ; réseaux sociaux en https uniquement | `app/Rules/HttpUrl.php` | `WorkflowTest` « bug n° 12 » ; `BooksTest` « refuse les liens d'achat… » ; `PublicSiteTest` « n'affiche que des liens http(s) même si la base contenait autre chose » ; `AdministrationTest` « refuse les liens non https » |
| **CSRF** | Jeton CSRF Laravel sur tous les formulaires (middleware `web`) ; cookies `SameSite=Lax` ; `form-action 'self'` | `bootstrap/app.php`, `config/session.php` | `HardeningTest` « cookie de session … SameSite=Lax » ; tests de formulaires (jeton fourni par le client de test) |
| **IDOR** (accès à l'objet d'un autre) | Toute lecture publique par `published()` / `approved()` (404 sinon) ; fichiers privés servis par contrôleur après contrôle de visibilité ; pièces jointes par URL signée 10 min **et** membre autorisé ; file de modération filtrée par permission avant la recherche par identifiant | `Content::scopePublished()`, `Book::scopePublished()`, `WorkController`, `BookController`, `CollaborationFileController`, `ReviewService::visibleTo()` | `PublicSiteTest` « renvoie 404 pour un brouillon… », « refuse le document d'un brouillon » ; `BooksTest` « sert les extraits … jamais pour un brouillon » ; `PublicWritesTest` « sert les pièces jointes par lien signé… », « limite la file aux avis que le membre peut modérer » |
| **Escalade de privilèges** | Règles R1–R8 dans un service unique, appliquées côté serveur ; permissions vérifiées par route (`permission:`) **et** dans chaque service ; `publish_content` séparé (décision 10) | `app/Services/RoleAssignment.php`, `UserManagement.php`, `RoleManagement.php`, `app/Http/Middleware/RequirePermission.php`, `ContentWorkflow.php` | `RulesTest` (R1–R8), `WorkflowTest` « bug n° 5 », tests « est réservé à … » de chaque lot |
| **Affectation de masse** | `$fillable` explicite sur chaque modèle (jamais `$guarded = []`) ; statut, rôle, email, auteur, modération, chemins de fichiers, compteurs hors `$fillable` ; `Model::shouldBeStrict()` hors production | tous les `app/Models/*.php`, `AppServiceProvider` | `RulesTest` « R3 … pas assignables en masse » ; `WorkflowTest` « champs sensibles » ; `BooksTest` « ignore les champs protégés » ; `PublicWritesTest` « crée un avis toujours en attente » ; `HardeningTest` « $fillable explicite » |
| **Force brute** | 5 tentatives/min par couple email + IP puis fenêtres 15 min / 1 h ; 5 essais/min sur le code 2FA ; 2FA TOTP obligatoire ; mot de passe ≥ 12 caractères, HIBP | `FortifyServiceProvider`, `app/Services/PasswordPolicy.php` | `LoginTest` « limite à 5 tentatives… », « limite à 5 essais … 2FA » ; `PasswordPolicyTest` (17 tests) |
| **Énumération de comptes / d'abonnés** | Message de connexion générique ; « mot de passe oublié » répond pareil ; newsletter : réponse identique, aucun email envoyé à une adresse déjà confirmée | `routes/auth.php`, `PasswordResetLinkController`, `NewsletterService::subscribe()` | `LoginTest` « même message pour un email inconnu » ; `PasswordResetTest` « répond pareil » ; `PublicWritesTest` « répond pareil dans tous les cas » |
| **SSRF** | Le serveur ne télécharge jamais une URL fournie par un utilisateur : vidéos intégrées par identifiant extrait d'une expression stricte (l'analyse IA, qui lisait des PDF, a été retirée, D71) | `app/Support/VideoEmbed.php` | `PublicSiteTest` « n'intègre que des URL YouTube/Vimeo/MP4 valides » |
| **Upload malveillant** | Type vérifié sur le **contenu** (finfo + signature `%PDF-`), jamais l'extension ; SVG refusé partout ; images décodées puis réencodées en WebP (métadonnées et charges utiles supprimées) ; noms aléatoires ; tailles bornées (5/2/20/50/10 Mo) ; documents et pièces jointes sur disque privé ; `.htaccess` qui interdit tout script dans `storage/` | `UploadService.php`, `ImageProcessor.php`, `MediaLibrary.php`, `storage/app/public/.htaccess` | `UploadsTest` « refuse un faux PDF », « refuse un SVG » ; `PublicWritesTest` « 3 pièces jointes vérifiées » (PHP et SVG déguisés) ; `AdministrationTest` « refuse SVG et faux PDF », « plus de 20 Mo » |
| **Traversée de chemin** | Les chemins servis doivent commencer par leur dossier prévu et ne pas contenir `..` | `WorkController::file()`, `BookController::excerpt()/ebook()`, `CollaborationFileController` | `PublicSiteTest` « refuse un chemin … qui sortirait du dossier prévu » ; `BooksTest` « refuse un chemin d'extrait hors du dossier prévu » |
| **Injection CSV / formule** | Séparateur `;`, guillemets doublés, préfixe `'` devant `= + - @` tabulation et retour chariot, BOM UTF-8 | `app/Support/SafeCsv.php` | `PublicWritesTest` « neutralise les formules (bug n° 11) » ; `AdministrationTest` « exporte en CSV sûr » |
| **Empoisonnement par l'en-tête Host** (trouvé à l'audit dynamique) | Toutes les URL absolues construites depuis `APP_URL` ; hôtes inconnus refusés (400) | `AppServiceProvider::pinApplicationUrl()`, `bootstrap/app.php` (`trustHosts`) | `HostHeaderTest` |
| **Redirection ouverte** | Les redirections externes n'utilisent que des adresses de configuration : le lien « Webmail » lit `WEBMAIL_URL` (https, sans identifiants dans l'URL, sinon 404), jamais un paramètre de la requête | `app/Support/Webmail.php`, `WebmailController.php` | `WebmailTest` « se désactive proprement sans URL valide » (http, `javascript:`, identifiants) |
| **Exposition des fichiers du projet** (racine web = `/htdocs` sur LWS Perso) | `.htaccess` racine : tout est réécrit vers `public/`, fichiers cachés et noms sensibles refusés, refus total sans `mod_rewrite` ; `Require all denied` dans chaque dossier hors `public/` (paquet) ; `.env` envoyé en `600` | `.htaccess`, `scripts/preparer-paquet.sh` | `DeploymentTest` « ne laisse joignable que public/ » ; sondes manuelles sur Apache (D73) : `.env`, `vendor/`, `storage/logs`, sauvegardes, encodages `%2e`, traversées → 403/404, aucune fuite |
| **Clickjacking** | `X-Frame-Options: DENY` + `frame-ancestors 'none'` | `SecurityHeaders.php` | `SecurityHeadersTest` ; `PublicSiteTest` « vraie 404 … en-têtes de sécurité » |
| **Fuite de données** | `APP_DEBUG=false` hors local ; description des collaborations chiffrée ; IP hachées (HMAC) ; jetons hachés SHA-256 ; secret 2FA et codes de secours chiffrés ; journal d'audit sans secret ; brouillons en `noindex` et jamais publics ; `.env` et fichiers cachés jamais servis | `Collaboration` (cast `encrypted`), `Audit.php`, `NewsletterService.php`, `public/.htaccess` | `PublicWritesTest` « description chiffrée en base » ; `TwoFactorMandatoryTest` « secret … chiffrés » ; `InvitationOnboardingTest` « jeton haché » ; `LoginTest` « trace … sans mot de passe » ; `HardeningTest` « aucun secret dans .env.example » |
| **Spam / robots** | Champ piège + Cloudflare Turnstile côté serveur + limitation par IP (5/min, 30/jour) sur les 4 formulaires ; pré-modération des avis | `SpamGuard.php`, `Turnstile.php`, `AppServiceProvider::publicRateLimits()` | `PublicWritesTest` « champ piège », « Turnstile côté serveur », « limite les envois par IP » |
| **Déni de service applicatif** | Pagination bornée (12/16/20/24/30) ; recherche ≤ 100 caractères ; limitation par IP des téléchargements (30/min), de la recherche (60/min), du sitemap/RSS (60/min), des compteurs (30/min) ; sitemap et RSS en cache 1 h | `WorksQuery.php`, `AppServiceProvider`, `SeoController.php`, `EventCounter.php` | `PublicSiteTest` « pagine avec une limite haute », « limite les téléchargements par IP », « plafonne les événements par IP » |
| **Gonflement des statistiques** | Une vue / un téléchargement par session et par élément, uniquement s'il est visible, plafond par IP ; aucune donnée identifiante stockée | `EventCounter.php` | `PublicSiteTest` (bloc « statistiques ») |

## 2. Base de données (cahier § 8.6)

| Exigence | Mise en œuvre | Preuve |
| --- | --- | --- |
| Comptes séparés en local | `yewtod_app` (SELECT, INSERT, UPDATE, DELETE) et `yewtod_migrate` (structure), créés par `database/sql/creer-utilisateurs.sql` ; connexion `migrate` utilisée uniquement par les commandes de structure | `DatabaseConfigTest` « compte distinct pour les migrations » |
| Configuration | MariaDB, `utf8mb4`, mode strict, fuseau UTC | `DatabaseConfigTest` « mode strict, utf8mb4 et UTC » |
| Intégrité | Clés étrangères avec règle explicite (`nullOnDelete` pour « invité par », « créé par », « modéré par » ; `cascadeOnDelete` pour épisodes, pièces jointes, liens similaires) ; index uniques (slugs, emails, numéro d'épisode, jetons) ; contraintes CHECK sur tous les statuts, types, longueurs des avis, prix ≥ 0, mois 1–12 | `PublicSiteTest` « la base refuse un type d'événement inconnu (CHECK) » ; `BooksTest` « la base refuse un prix négatif » ; `RulesTest` « bug n° 10 » |
| Concurrence | Transactions + `lockForUpdate()` : invitation à usage unique, dernier Super Admin, numéro d'épisode, bascule des programmés, slugs, confirmation newsletter | `InvitationOnboardingTest` « une seule fois » ; `RulesTest` « au moins un Super Admin » ; `WorkflowTest` « bug n° 1 » |
| Secrets en base | Argon2id ; jetons SHA-256 ; 2FA chiffrée ; description chiffrée ; IP hachées | voir § 1 « Fuite de données » ; `DatabaseConfigTest` « Argon2id » |
| Lecture publique | Scopes `published()` / `approved()` ; pagination obligatoire | voir § 1 « IDOR » |
| Production | `DB::prohibitDestructiveCommands()` ; seeders de démo refusés hors local | `HardeningTest` « bloque les commandes destructrices », « seeders … refusent de tourner hors local » |
| Rétention | Purge quotidienne `yewtod:purge` : collaborations 24 mois, inscriptions non confirmées 7 jours, invitations closes 90 jours, journal d'audit 12 mois, statistiques 25 mois | `BackupAndRetentionTest`, `PublicWritesTest` « purge » |
| Traçabilité | Journal d'audit (connexions, rôles, statuts, invitations, 2FA, publications, suppressions, modération, exports) sans secret, IP hachée | `LoginTest`, `RulesTest` « réinitialisation 2FA … journalisée » |

### Cas du mutualisé LWS : un seul utilisateur par base

Le panel LWS ne permet qu'un utilisateur par base. Si `DB_MIGRATE_USERNAME` est vide, la connexion de migration retombe sur cet utilisateur unique (`DatabaseConfigTest`). **Ce que cela change** : en production, le compte utilisé par le site a aussi les droits de structure (DROP, ALTER) ; une injection SQL réussie aurait donc plus de pouvoir qu'en local. **Comment c'est compensé** :

1. aucune injection possible par construction (paramètres liés, listes blanches, test d'architecture contre le SQL interpolé) ;
2. lecture publique uniquement par scopes, aucune erreur SQL affichée (`APP_DEBUG=false`) ;
3. `DB::prohibitDestructiveCommands()` bloque `migrate:fresh`, `db:wipe`, etc. en production ;
4. migrations lancées seulement par le script de déploiement, **après** une sauvegarde et une confirmation (lot 10) ;
5. base inaccessible depuis Internet (accès distant désactivé chez LWS), phpMyAdmin derrière l'espace client LWS protégé par 2FA ;
6. sauvegardes chiffrées quotidiennes avec restauration testée (§ 3).

## 3. Sauvegardes chiffrées et restauration

- `php artisan yewtod:backup` (planifiée chaque nuit à 2 h 30) : dump SQL en PHP pur + fichiers privés et publics dans une archive, chiffrée par **XChaCha20-Poly1305** (libsodium, flux par blocs authentifiés), clé dérivée de `BACKUP_PASSPHRASE` par **Argon2id**. La phrase de passe est distincte d'`APP_KEY` ; conservez **les deux** hors du serveur (gestionnaire de mots de passe) : sans `APP_KEY`, les descriptions chiffrées et secrets 2FA restaurés seraient illisibles.
- Rotation automatique : 7 quotidiennes, la plus récente de chacune des 4 dernières semaines et des 6 derniers mois.
- Sans phrase de passe, la commande se désactive avec un message clair (`BackupAndRetentionTest`).
- Copie hors de l'hébergeur : `scripts/recuperer-sauvegardes.sh` (rsync en SSH, depuis WSL), à lancer chaque semaine.
- **Restauration** : `php artisan yewtod:backup:restore storage/app/backups/yewtod-AAAAMMJJ-HHMMSS.ybk --dossier=/tmp/restau [--importer]`. Le déchiffrement échoue si la phrase de passe est fausse ou si le fichier a été altéré (`BackupAndRetentionTest`).

**Test de restauration réalisé le 16/11/2026** (environnement de génération) : sauvegarde de la base de démonstration, restauration dans une base vide `yewtod_restore` avec `--importer --force`, puis comparaison : mêmes nombres de lignes pour `users` (5), `contents` (19), `books` (10), `reviews` (20), `collaborations` (8), `newsletter_subscribers` (5) ; description chiffrée identique octet pour octet ; liste des fichiers privés identique. À refaire au moins une fois sur la préproduction LWS avant la mise en ligne (voir `docs/DEPLOIEMENT.md`).

## 4. En-têtes HTTP (cahier § 8.4)

CSP stricte avec nonce (scripts : `self`, nonce, Turnstile, YouTube ; `frame-ancestors 'none'` ; `object-src 'none'` ; `base-uri 'self'` ; `form-action 'self'`), HSTS en production / HTTPS, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` restrictive, `Cross-Origin-Opener-Policy: same-origin`. Les styles inline ne sont plus tolérés (D67, `style-src 'self' 'nonce-…'`) ; `Cross-Origin-Resource-Policy: same-site`, `X-Permitted-Cross-Domain-Policies: none`, aucun `X-Powered-By`, aucun cookie `XSRF-TOKEN`. Hôte verrouillé sur `APP_URL` (`TrustHosts` + `URL::forceRootUrl`, D66). Fichier : `app/Http/Middleware/SecurityHeaders.php` ; tests : `SecurityHeadersTest`.

## 5. Dépendances

`composer audit` : aucune vulnérabilité. `npm audit` : aucune vulnérabilité après la mise à jour de **pdf.js 5 → 6.5** (GHSA-hq66-cqwq-w95j, exécution de JavaScript à l'ouverture d'un PDF piégé ; pdf.js analyse les PDF envoyés dans l'éditeur, avec `isEvalSupported: false`). À relancer avant chaque mise en ligne (`scripts/deployer.sh` le fait).

## 6. Matrice de couverture des tests

### Règles R1–R8 (cahier § 6)

| Règle | Test(s) |
| --- | --- |
| R1 | `RulesTest` « R1 : personne ne modifie son propre rôle… », « …son propre statut », « R1 + R6 : il reste toujours au moins un Super Admin » |
| R2 | `InvitationOnboardingTest` « active le compte … statut actif (R2) », « R2 : refuse une invitation expirée ou révoquée » |
| R3 | `RulesTest` « R3 : … pas assignables en masse », « R3 : le profil ignore un email ou un rôle injecté via Livewire » |
| R4 | `InvitationOnboardingTest` « active le compte : rôle de l'invitation (R4)… » |
| R5 | `RulesTest` « R5 : non-escalade… », « R5 : le rôle système… » ; `InvitationOnboardingTest` « R5 : refuse d'inviter… », « R5 : seul un Super Admin invite un Super Admin » |
| R6 | `RulesTest` « R6 : un Administrateur ne peut ni suspendre ni supprimer un Super Admin », « R6 : un Administrateur gère un Éditeur » |
| R7 | `InvitationOnboardingTest` « R7 : l'invitation est émise au nom de son créateur » ; `RulesTest` « R7 : … révoquée que par son auteur ou un supérieur » |
| R8 | `RulesTest` « R8 : seul un Super Admin modifie un rôle système », « R8 : on n'accorde … que une permission que l'on possède », « R8 : un rôle système ne peut pas être supprimé… » |

### Politique de mot de passe (cahier § 8.2)

Chaque critère a son test dans `PasswordPolicyTest` : 12 minimum, 128 maximum, minuscule, majuscule, chiffre, caractère spécial, fuites connues (HIBP simulé), nom, partie locale de l'email, « yewtod », 3 caractères identiques, suites évidentes, 5 derniers mots de passe (et historique borné), confirmation, Argon2id ; mêmes règles à l'onboarding (`InvitationOnboardingTest`), au changement (`AccessAndProfileTest`), à la réinitialisation (`PasswordResetTest`) et à la création du premier Super Admin (`SuperAdminCommandTest`).

### Exigences de la section 8

| § | Exigence | Test(s) |
| --- | --- | --- |
| 8.1.1 | 2FA obligatoire | `TwoFactorMandatoryTest` « redirige vers la configuration 2FA » |
| 8.1.2 | TOTP, confirmation par un premier code | `TwoFactorMandatoryTest` « enrôle la 2FA … confirmation TOTP » |
| 8.1.3 | 8 codes de secours, une seule fois, régénérables | `TwoFactorMandatoryTest` « régénère les codes, invalide les anciens », « n'affiche plus les codes » ; `LoginTest` « code de secours une seule fois » |
| 8.1.4 | Code 6 chiffres, 5 essais/min, anti-rejeu | `LoginTest` « refuse un code TOTP déjà utilisé », « limite à 5 essais » |
| 8.1.5 | Secret chiffré | `TwoFactorMandatoryTest` « stocke le secret TOTP … chiffrés » |
| 8.1.6 | Reconfirmation du mot de passe | `TwoFactorMandatoryTest` « exige une reconfirmation… » ; `AccessAndProfileTest` « exige la reconfirmation … changer un rôle » |
| 8.1.7 | Réinitialisation 2FA par Super Admin, journalisée, notifiée | `RulesTest` « réinitialisation 2FA … » |
| 8.1.8 | Emails de notification | `TwoFactorMandatoryTest` « … et notifie » ; `AccessAndProfileTest` « change le mot de passe … et notifie » |
| 8.2 | Politique de mot de passe | voir ci-dessus |
| 8.3 | Limitation, message générique, mot de passe oublié 60 min, sessions, déconnexion des autres appareils, CSRF | `LoginTest`, `PasswordResetTest`, `AccessAndProfileTest` « liste les sessions actives… », `HardeningTest` « cookie de session » |
| 8.4 | Autorisations, fichiers, écritures publiques, anti-spam, liens, affichage, en-têtes, secrets, dépendances | § 1 de ce document |
| 8.5 | Journal d'audit, pages légales, rétention, suppression RGPD, sauvegardes | `LoginTest` « trace les connexions », `AccessAndProfileTest` « réserve le journal d'audit au Super Admin », `PublicSiteTest` « sert … mentions légales et confidentialité », `BackupAndRetentionTest`, `AdministrationTest` « supprime sur demande RGPD » |
| 8.6 | Base de données | § 2 de ce document |

### Bugs de la section 11

| Bug | Test(s) |
| --- | --- |
| 1 Numéro d'épisode | `WorkflowTest` « bug n° 1 » |
| 2 Nom, logo, favicon non lus | `AdministrationTest` « applique nom… », « enregistre logo et favicon … et les sert sur le site » |
| 3 Temps de lecture | `WorkflowTest` « bug n° 3 » |
| 4 « Mise en avant » sans effet | `PublicSiteTest` « fait piloter « Travaux mis en avant » … (bug n° 4) » |
| 5 Contributeur qui publie | `WorkflowTest` « bug n° 5 » ; `EditorTest` « un Contributeur voit « Soumettre en relecture » » |
| 6 Médiathèque et newsletter sans écran | `AdministrationTest` blocs « médiathèque » et « newsletter » |
| 7 Pages en dur | `AdministrationTest` « édite Meet (dont la FAQ) et l'accueil, avec les clés cohérentes » |
| 8 Champs riches non saisissables | `EditorTest` « ouvre l'éditeur pour chacun des 8 types » ; `PublicSiteTest` « propose les 3 gabarits… », « liste les épisodes… », graphiques |
| 9 Similaires par titre | `WorkflowTest` « bug n° 9 » ; `BooksTest` « relie les ouvrages similaires par identifiant » |
| 10 « Invité par » bloquant | `RulesTest` « bug n° 10 » |
| 11 Export CSV | `PublicWritesTest` « bug n° 11 » ; `AdministrationTest` « exporte en CSV sûr » |
| 12 Liens `javascript:` | `WorkflowTest` « bug n° 12 » ; `BooksTest` ; `PublicSiteTest` ; `AdministrationTest` |
| 13 Fiche livre anglaise, achat vers `#`, USD | `BooksTest` « fiche 100 % en français … (bug n° 13) » |
| 14 Pas de mot de passe oublié / 404 / pages légales | `PasswordResetTest` ; `PublicSiteTest` « vraie 404 », « mentions légales et confidentialité » |
| 15 Rendu côté navigateur, SEO | `PublicSiteTest` « rend les métadonnées côté serveur (bug n° 15) », « JSON-LD et Open Graph » |
| 16 Statistiques limitées au statut « publié » | `WorkflowTest` « bug n° 16 » ; `AdministrationTest` « compte les publiés, y compris les programmés échus » |
| 17 Code mort | `HardeningTest` « bug n° 17 » |
