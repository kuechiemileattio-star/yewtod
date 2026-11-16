# Décisions techniques

Ce fichier trace chaque choix pris sans validation intermédiaire (consigne du prompt, § 0). Les décisions de la section 13 du cahier ne sont jamais remises en cause ; les lignes ci-dessous les **appliquent** ou comblent un vide.

| # | Sujet | Décision | Pourquoi |
| --- | --- | --- | --- |
| D1 | Version de Laravel | Laravel **13.42** (dernière stable au 15/11/2026), PHP ^8.3 | Exigence « dernière version stable » ; PHP 8.3 = minimum LWS |
| D2 | Fortify | Version **1.36.2** épinglée (`~1.36.2`) | Les versions 1.37+ exigent `laravel/passkeys`, paquet que l'environnement de génération ne pouvait pas télécharger. 1.36 couvre tout le besoin (TOTP, codes de secours, confirmation de mot de passe). Mise à jour possible plus tard : `composer require laravel/fortify:^1.42` |
| D3 | `composer.lock` | Non versionné au premier commit ; **à générer chez toi** par `composer update` puis à committer | Packagist était inaccessible pendant la génération : les paquets ont été installés depuis GitHub, le lock produit pointait vers des chemins locaux. Voir `docs/INSTALLATION.md` § 5 |
| D4 | Driver de base | Driver Laravel `mariadb` (pas `mysql`) | Colle au serveur réel de LWS (décision 12) ; aucune fonction MySQL 8+/9 |
| D5 | Base de tests | Base MariaDB `yewtod_test` (et non SQLite) | Les contraintes CHECK, clés étrangères, verrous `lockForUpdate()` et le mode strict doivent être testés sur le vrai moteur |
| D6 | Proxys de confiance | Aucun par défaut (`TRUSTED_PROXIES` vide) | Faire confiance à `*` permettrait de falsifier l'IP et de contourner la limitation par IP |
| D7 | Polices | Aucune police distante : pile système (sans-serif) + serif système pour l'éditorial | Pas de requête vers un tiers (RGPD), CSP `font-src 'self'` |
| D8 | Alpine.js | **Build CSP** de Livewire/Alpine (`csp_safe`) | Permet une CSP sans `unsafe-eval` ; toute logique Alpine vit dans `resources/js/app.js` (`Alpine.data`) |
| D9 | Styles inline | `style-src 'self' 'unsafe-inline'` | Alpine/Livewire écrivent l'attribut `style` (x-show, transitions). Le cahier n'exige l'absence d'`unsafe-inline` que pour les scripts |
| D10 | Mots de passe compromis en local | `HIBP_ENABLED=false` dans le `.env` de génération | L'environnement de génération ne joint pas l'API HIBP ; la règle est testée avec une réponse simulée. **À laisser à `true` chez toi et en ligne** |
| D11 | Enrôlement 2FA | Code envoyé par email exigé avant d'enregistrer un appareil, sauf juste après l'onboarding | Empêche un voleur de mot de passe d'enrôler son téléphone sur un compte sans 2FA |
| D12 | Routes Fortify | `Fortify::ignoreRoutes()` puis routes redéclarées (`routes/auth.php`), URL en français | Limitation par route et réponse non énumérante pour « mot de passe oublié » |
| D13 | Limitation « croissante » | Fenêtres empilées 1 min / 15 min / 1 h + plafond par IP | Laravel n'a pas de délai exponentiel natif ; l'empilement donne une attente qui s'allonge |
| D14 | Création du compte invité | Le compte est créé au moment de l'onboarding (statut « invité » puis « actif » dans la même transaction) | Évite des comptes sans mot de passe en base ; R1/R2 restent respectées et testées |
| D15 | Réinitialisation de mot de passe | Jeton haché par Laravel (bcrypt/Argon) plutôt que SHA-256 | Plus fort que SHA-256 ; c'est le mécanisme natif, testé |
| D16 | RBAC | Tables maison `roles` / `permissions` (pas `spatie/laravel-permission`) | Le cahier l'autorise ; R1–R8 sont plus lisibles dans un service unique, sans cache de permissions à invalider |
| D17 | Permission `publish_content` | 10e permission ajoutée (Éditeur, Administrateur, Super Admin) | Décision 10 : le Contributeur soumet, l'Éditeur publie |
| D18 | Captures responsives | Playwright piloté par `tests/visual/captures.mjs`, Chrome système, JPEG qualité 55 | Exigence décision 14 ; JPEG pour limiter le poids du dépôt |
| D19 | Commande `yewtod:dev-session` | Outil local (refusé hors `local`) qui ouvre une session pour les captures | Les captures du dashboard nécessitent d'être connecté malgré la 2FA |
| D20 | Bordure des champs | Jeton `line-strong` (≥ 3:1) pour les champs, `line` réservé aux séparateurs | WCAG 1.4.11 : la couleur de bordure du cahier (#CAD5DF) est trop claire pour délimiter un champ |
| D21 | Accents en texte | Variantes `accent-ink` (#B13A26) et `accent-2-ink` (#1D6874) en clair | L'orange et le bleu-vert de la palette n'atteignent pas 4,5:1 sur fond clair |
| D22 | Muted clair | #5A6679 au lieu de #657286 | #657286 sur #F7F3EC est juste sous 4,5:1 |
| D23 | Axes thématiques | 8 axes provisoires (`app/Support/Taxonomy.php`) | Le cahier en annonce 8 sans les nommer ; à ajuster dans ce fichier |
| D24 | Documents des publications | Disque privé + contrôleur (au lieu du disque public) | Un PDF de brouillon ne doit jamais être joignable ; permet le comptage réel des téléchargements |
| D25 | Éditeur riche | `contenteditable` minimal + HTML Purifier côté serveur (pas TipTap) | Moins de dépendances, compatible build CSP ; la sécurité repose sur le nettoyage serveur |
| D26 | CSV des visualisations | Analysé à l'envoi et stocké en JSON dans `meta.blocks[].data` | Le navigateur ne télécharge jamais de CSV ; données bornées (500 lignes, 20 séries) |
| D27 | Affichage du HTML des sections | HTML Purifier appliqué **à l'enregistrement et à l'affichage** (`TextStructurer`) | Défense en profondeur : une ligne écrite en base par un autre chemin (seeder, intervention manuelle) reste nettoyée |
| D28 | Aperçu vidéo de 2 minutes | Paramètre `end=120` du lecteur `youtube-nocookie.com` (pas d'API JavaScript YouTube) | Aucun script tiers à charger, CSP inchangée, pas de cookie YouTube avant lecture |
| D29 | Vidéos MP4/WebM externes | `media-src 'self' https:` dans la CSP | Un média ne peut pas exécuter de script ; permet les fichiers vidéo hébergés ailleurs en HTTPS |
| D30 | Aperçu réel des brouillons | URL signée 30 min **et** membre connecté, actif, 2FA, avec `manage_articles` ; en-tête `X-Robots-Tag: noindex` | Une URL signée seule fuiterait le brouillon si elle était transmise ; on garde le principe « aucun brouillon atteignable publiquement » |
| D31 | Filtre « Auteur » de /works | Membres auteurs (colonne `author_id`), pas les auteurs libres de `meta.authors` | Liste fermée et indexable, sans recherche floue dans le JSON ; les auteurs libres restent trouvables par la recherche plein texte |
| D32 | « Travaux mis en avant » | Contenus `is_featured` d'abord (4 max), complétés par les plus récents si besoin | Le drapeau pilote vraiment le bloc (bug n° 4) sans laisser un bloc vide |
| D33 | sitemap / RSS / robots.txt | Générés par Laravel (sans `spatie/laravel-sitemap`), cache 1 h vidé à chaque écriture de contenu ; `public/robots.txt` supprimé au profit d'une route | Une dépendance de moins ; robots.txt peut ainsi pointer l'URL absolue du sitemap |
| D34 | Comptage des vues | Une fois par session et par contenu + plafond de 30 événements/min par IP hachée ; aucun identifiant stocké dans `content_events` | Cahier § 5.3 ; aucune donnée personnelle conservée pour une statistique |
| D35 | Mentions légales | Identité de l'éditeur dans `.env` (`LEGAL_*`), hébergeur LWS par défaut (coordonnées des CGV LWS, à revérifier) | Champs obligatoires (LCEN) qui changent rarement ; affichés « à compléter » tant qu'ils sont vides |
| D36 | Statuts des livres | Mêmes valeurs que les publications (CHECK), circuit simplifié : brouillon, programmé, publié ; `manage_books` suffit pour publier | Le Contributeur n'a pas `manage_books` : la séparation de la décision 10 ne concerne que les publications |
| D37 | Prix | `decimal(8,2)` + CHECK ≥ 0, saisie « 19,90 » acceptée et normalisée, affichage « 19,90 € » | Jamais de flottant pour une somme ; format français (décision 5) |
| D38 | Ebook « lecture du PDF hébergé » | Servi `inline` par un contrôleur (disque privé, livre visible uniquement), compté comme téléchargement | Le cahier demande une lecture en ligne ; aucun fichier n'est exposé dans `public/` |
| D39 | Livres dans le sitemap et la recherche | Sources étiquetées dans le conteneur (`sitemap.sources`, `search.sources`) | Les modules restent indépendants : un nouveau module s'ajoute sans modifier les contrôleurs existants |
| D40 | Tailles de fichiers | Formateur français maison (`App\Support\Format::fileSize` : o, Ko, Mo) | `Number::fileSize()` de Laravel affiche des unités anglaises (B, KB) |
| D41 | Turnstile en panne | Clé configurée + Cloudflare injoignable = envoi **refusé** ; sans clé = vérification désactivée (honeypot, limitation et pré-modération restent actifs) | On préfère bloquer un envoi que d'ouvrir la porte aux robots ; décision 8 pour l'absence de clé |
| D42 | Honeypot | Champ `website` positionné hors écran (classe CSS `.honeypot`), `tabindex=-1`, message d'erreur neutre | Les robots remplissent ce champ ; un humain ne le voit pas ; le message n'indique pas la cause |
| D43 | Limitation des formulaires | 5 envois/min et 30/jour par formulaire et par IP (`throttle:public-form`) | Cahier § 8.4 ; assez large pour un humain, coûteux pour un robot |
| D44 | Désinscription « en un clic » | Lien → page avec un seul bouton qui envoie un POST | Un GET qui désinscrit serait déclenché par les antivirus et aperçus de liens des messageries ; un clic suffit toujours |
| D45 | Abonné déjà confirmé | Aucune action, aucun email, message identique | Pas d'énumération (§ 5.6) et pas d'email révélateur envoyé à la personne |
| D46 | Jetons newsletter | 40 caractères aléatoires, SHA-256 stocké, comparaison par recherche du haché (index unique) | Le haché est comparé par la base : pas de comparaison caractère par caractère côté PHP, donc pas de fuite temporelle exploitable |
| D47 | Avis immuables | Événement `updating` du modèle qui refuse toute modification du nom, du texte ou de la cible | Cahier § 5.4 : la modération ne change que le statut, même en cas d'erreur de code |
| D48 | Description des collaborations | Cast `encrypted` (AES-256 avec APP_KEY) ; recherche plein texte impossible dans ce champ, assumée | Cahier § 8.6 ; on filtre par statut, date, nom ou organisation |
| D49 | Export CSV | Séparateur `;`, BOM UTF-8, guillemets doublés, préfixe `'` devant `= + - @` tabulation et retour chariot | Bug n° 11 : colonnes décalées et injection de formule |
| D50 | Logo et favicon | Images réencodées en WebP (logo 480 px, favicon 192 px), SVG refusé | Un SVG peut contenir du script ; WebP est accepté comme favicon par les navigateurs actuels |
| D51 | Pages éditables | Le schéma d'une page = les clés de ses valeurs par défaut (`PageDefaults`) ; toute autre clé est ignorée | Le JSON reste fermé, comme `meta` pour les contenus |
| D52 | Médiathèque | Fichiers **publics** (images WebP + srcset, PDF), réutilisation par URL copiée ; les PDF privés des publications restent hors médiathèque | La médiathèque sert des ressources destinées à être publiées ; le HTML Purifier n'autorisant pas `<img>` dans les sections, l'insertion directe dans le corps n'est pas proposée (partiel assumé, voir conformité) |
| D53 | Orphelins de la médiathèque | Recherche de la racine du nom aléatoire dans contenus, livres, pages et réglages ; jamais avant 24 h | Un fichier tout juste envoyé n'est pas encore référencé |
| D54 | Vue d'ensemble | Contrôleur classique (pas de Livewire), graphique `SvgChart` côté serveur, comptes via `published()` | Aucune interactivité nécessaire ; mêmes règles de visibilité que le site (bug n° 16) |
| D55 | Purges | Une seule commande `yewtod:purge` (quotidienne, 3 h 15) : collaborations 24 mois, inscriptions non confirmées 7 jours, invitations closes 90 jours, journal d'audit 12 mois, statistiques 25 mois | Durées réglables dans `config/yewtod.php` / `.env` ; les invitations échues passent d'abord « expirées » |
| D56 | Sauvegardes | Dump SQL en PHP pur + zip, chiffrés XChaCha20-Poly1305 (libsodium secretstream), clé Argon2id dérivée de `BACKUP_PASSPHRASE` | Pas de dépendance à `mysqldump`/`exec` (souvent bloqués en mutualisé) ; chiffrement authentifié : toute altération est détectée |
| D57 | Restauration | `yewtod:backup:restore` déchiffre et extrait ; `--importer` rejoue le SQL après confirmation (`--force` pour les scripts) | La restauration écrase la base : elle n'est jamais implicite |
| D58 | Audit d'accessibilité | axe-core (WCAG 2.1 A/AA) sur toutes les pages, clair et sombre, avec `bypassCSP` côté outil uniquement | Vérification reproductible (`tests/visual/a11y.mjs`) ; la CSP du site reste intacte |
| D59 | Sommaire des publications | Liste de liens avec `aria-current` (et non `role=tablist`) ; les onglets de la fiche livre gardent `role=tab` | Structure ARIA valide relevée par l'audit ; sans JavaScript, ce sont de simples ancres |
| D60 | Vert « succès » en clair | #17663F au lieu de #1E7A4C | Les pastilles « Publié » (texte sur fond teinté à 15 %) n'atteignaient pas 4,5:1 ; nouveau test de contraste des pastilles |
| D61 | pdf.js | Mise à jour 5 → 6.5 | Vulnérabilité haute GHSA-hq66-cqwq-w95j (exécution de JavaScript via un PDF piégé) |
| D62 | `.htaccess` | `Options -Indexes`, fichiers cachés refusés, cache long des assets Vite, `storage/` sans exécution de script | Défense en profondeur côté Apache (LWS) et performance |
| D63 | Mode de déploiement | Assets compilés dans WSL puis `rsync` en SSH (pas de `git pull` sur le serveur) ; code précédent archivé (5 versions) pour le retour arrière | Pas de Node en production (décision 12) ; `public/build` n'est pas versionné ; un retour arrière ne dépend pas de Git sur le serveur |
| D64 | Préproduction protégée | Middleware HTTP Basic activé par `PREPROD_USER` / `PREPROD_PASSWORD_HASH` (haché), `X-Robots-Tag: noindex` ; `/up` reste accessible pour la sonde | Cahier § 12.3 « accès protégé » sans dépendre des options `.htpasswd` du panel |
| D65 | Vérification de la production | Commande `yewtod:verifier-production` (bloquante pour debug, HTTPS, cookie Secure, clé, base, stockage, assets, file, sauvegardes ; informative pour SMTP, Gemini, Brevo, Turnstile, mentions légales) | Rend visibles les fonctions désactivées faute de clé (décision 8) et empêche une mise en ligne en `APP_DEBUG=true` |
