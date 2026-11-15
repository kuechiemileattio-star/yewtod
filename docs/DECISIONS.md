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
