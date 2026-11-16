# Audit de sécurité dynamique — 16/11/2026

Complément de `docs/SECURITE.md` (protections et tests unitaires). Ici, l'application tourne réellement et on l'attaque avec des outils du marché et des sondes manuelles.

## 1. Périmètre et méthode

- **Cible** : la branche `v2` servie en **mode production** (`APP_ENV=production`, `APP_DEBUG=false`) avec les données de démonstration :
  - port 8001 : limitations par IP réelles ;
  - port 8002 : cache non persistant, pour que la limitation ne masque pas les vulnérabilités aux scanners ;
  - port 8080 : **Apache 2.4 + PHP**, pour tester le vrai `public/.htaccess` (LWS utilise Apache).
- **Outils** :
  - OWASP ZAP 2.17 (exploration + scan actif) ;
  - sqlmap (niveau 3, risque 2, techniques BEUST) ;
  - nuclei 3.11 (modèles HTTP) ;
  - nikto ;
  - sondes manuelles en Python et Playwright, conservées dans `tests/securite/`.
- **Hors périmètre** :
  - la configuration réelle chez LWS (TLS, version de PHP, Apache) : à vérifier après déploiement (§ 5) ;
  - les attaques par déni de service volumétrique ;
  - l'ingénierie sociale ;
  - les services tiers (Cloudflare, Brevo, Google).

## 2. Vulnérabilités trouvées et corrigées

| # | Gravité | Constat | Preuve avant correction | Correction | Test |
| --- | --- | --- | --- | --- | --- |
| 1 | **Haute** | **Empoisonnement par l'en-tête Host** : les URL absolues (sitemap et RSS mis en cache 1 h, liens des emails de réinitialisation et d'invitation) reprenaient le `Host` envoyé par le client. Une demande « mot de passe oublié » forgée pouvait envoyer à la victime un lien livrant son jeton à un domaine tiers. | `curl -H "Host: attaquant.example" /sitemap.xml` → `<loc>http://attaquant.example`, puis servi aux visiteurs suivants depuis le cache | `URL::forceRootUrl(APP_URL)` + `forceScheme('https')` ; `TrustHosts` limité à l'hôte d'`APP_URL` (400 sinon) | `HostHeaderTest` (sitemap, lien de réinitialisation, hôtes acceptés) |
| 2 | Moyenne | **CSP avec `style-src 'unsafe-inline'`** (ZAP). Toléré jusque-là (D9), alors qu'Alpine n'en a pas besoin quand les styles sont passés en objet. | En-tête CSP | Styles dynamiques en objets (`style.setProperty`, autorisé par la CSP) ; nonce transmis à Livewire pour sa feuille de style injectée ; `style-src 'self' 'nonce-…'`. Parcours complet du site : **0 violation de CSP**, interactions vérifiées (menu, jauge, modales, Ctrl K) | `HostHeaderTest` « CSP sans unsafe-inline », `tests/securite/csp.mjs`, `interactions.mjs` |
| 3 | Faible | **Lien de réseau social non revérifié à l'affichage** : un lien `javascript:` présent en base (validation contournée) était rendu dans le pied de page. Non exploitable grâce à la CSP (aucune exécution), mais défense en profondeur absente. | `tests/securite/xss-stocke.mjs` : lien `javascript:` présent sur toutes les pages | Filtre `HttpUrl::isSafe()` dans le pied de page | `HostHeaderTest` « ne rend jamais un lien de réseau social non http(s) » |
| 4 | Faible | **Version de PHP révélée** (`X-Powered-By: PHP/8.3.6`, nikto, nuclei, ZAP) : aide à cibler une faille connue de la version | En-tête | `header_remove('X-Powered-By')` | `HostHeaderTest` « ne révèle pas la technologie » |
| 5 | Faible | **Cookie `XSRF-TOKEN` lisible par JavaScript** (nikto, ZAP) : inutile, le site n'utilise pas axios | `Set-Cookie: XSRF-TOKEN` sans `HttpOnly` | Middleware CSRF sans ce cookie ; le jeton reste vérifié partout | `HostHeaderTest` (absence du cookie, CSRF toujours vérifié) ; témoins Livewire OK |
| 6 | Info | En-têtes `Cross-Origin-Resource-Policy` et `X-Permitted-Cross-Domain-Policies` absents ; `nosniff` absent sur les fichiers statiques servis par Apache | nuclei, ZAP | Ajoutés (middleware et `.htaccess`) | `HostHeaderTest` ; vérifié sur Apache |

## 3. Ce qui a résisté

| Attaque | Résultat |
| --- | --- |
| Injection SQL (sqlmap) | **Aucun paramètre injectable**. Testés : `/works` (`q`, `categorie`, `axe`, `tri`, `serie`, `auteur`, `du`, `au`), `/books` (`q`, `categorie`, `auteur`, `annee`, `tri`), `/recherche` (`q`, en-têtes User-Agent et Referer) ; POST newsletter (`email`), avis (`name`, `body`), collaborations (`name`, `organization`, `email`, `subject`, `kind`, `description`), avec gestion du jeton CSRF |
| Scan actif ZAP (XSS, injections, traversée, inclusion, commandes…) | Aucune alerte haute ; les alertes moyennes et faibles sont celles du § 2 |
| XSS réfléchi (25 points d'entrée) | Aucune réflexion non échappée |
| XSS stocké (charges écrites en base dans tous les champs affichés : titres, sections, méta, références, graphiques, chapitres, livres, avis, réglages, réseaux, collaborations, profil) | **Aucune exécution** sur 19 pages publiques et du dashboard (navigateur réel, détection des boîtes de dialogue) |
| Escalade de privilèges via Livewire (appels de méthodes et modification de propriétés depuis la console du navigateur) | 17 tentatives refusées : propriétés verrouillées, publication par un Contributeur, avis de livre par un Contributeur, rôle/suspension/suppression/2FA du Super Admin par un Administrateur, changement d'email via le profil… **Témoins positifs** : les mêmes appels réussissent quand ils sont autorisés (le refus n'est donc pas un artefact) |
| CSRF | POST sans jeton : 419 |
| Méthodes HTTP (PUT, DELETE, PATCH, TRACE) | Refusées |
| Traversée de chemin et fichiers sensibles (`.env`, `.git`, `composer.json`, journaux, encodages `%2f`, `%252f`, `..`) | 403/404 partout, aussi bien sous `artisan serve` que sous Apache |
| Scripts déposés dans `storage/` (`.php`, `.phtml`) | 403 sous Apache, jamais exécutés |
| Redirection ouverte (`//attaquant.example/`, paramètres `redirect`, `next`, `url`) | Aucune (Apache normalise vers une URL locale) |
| Énumération par le temps de réponse à la connexion | Écart entre email existant et inconnu inférieur à 50 ms (médiane sur 12 essais) |
| Upload Livewire sans signature, instantané Livewire forgé | 401 / 404 |
| Accès anonyme au dashboard et aux exports | Redirection vers la connexion |
| Fuite d'erreurs (paramètres de 5 000 caractères, tableaux inattendus) | Aucune trace, aucun message SQL |
| Limitation par IP en conditions réelles | Connexion bloquée à la 6e tentative, newsletter au 6e envoi, mot de passe oublié à la 4e demande, téléchargements à la 31e requête, recherche à la 61e |

## 4. Constats acceptés (non corrigés, avec justification)

| Constat | Pourquoi il est accepté |
| --- | --- |
| `media-src 'self' https:` (ZAP : directive générique) | Nécessaire pour les vidéos MP4/WebM hébergées ailleurs (D29) ; un média ne peut pas exécuter de script ; les URL ne sont saisies que par des membres |
| Pas de `Cross-Origin-Embedder-Policy` | `require-corp` casserait les lecteurs YouTube et Vimeo intégrés |
| Pas d'attribut SRI sur les assets | Assets servis depuis notre propre domaine, avec une empreinte dans leur nom ; SRI n'apporte rien sans CDN tiers |
| `robots.txt` cite `/dashboard` et `/apercu` | Ces chemins sont protégés par authentification et 2FA ; les cacher n'ajouterait aucune sécurité |
| Version de PHP détectée par nuclei | Les avis CVE relevés portent sur le PHP de l'environnement d'audit ; en production, l'en-tête est retiré. **Choisir la dernière version corrective de PHP 8.3 ou 8.4 chez LWS** |
| Cookies sans `Secure` | Uniquement parce que l'audit tourne en HTTP ; `SESSION_SECURE_COOKIE=true` est exigé par `yewtod:verifier-production` |

## 5. À vérifier après le déploiement chez LWS

1. https://securityheaders.com et https://observatory.mozilla.org sur `yewtod.com` : en-têtes présents en HTTPS, HSTS.
2. https://www.ssllabs.com/ssltest/ : TLS 1.2 minimum, note A.
3. `curl -I https://yewtod.com -H "Host: attaquant.example"` doit répondre 400 ou rediriger vers `yewtod.com`, jamais refléter l'hôte.
4. `curl -I https://yewtod.com/.env` → 403 ; `https://yewtod.com/storage/` → 403.
5. La version de PHP affichée par `php -v` en SSH est la dernière corrective.
6. Rejouer `tests/securite/sondes-anonymes.py https://preprod.yewtod.com` (préproduction, avec ses identifiants Basic) et `limites.py` en adaptant l'URL.

## 6. Rejouer l'audit

```bash
# Serveur en mode production local (données de démonstration chargées)
APP_ENV=production APP_DEBUG=false APP_URL=http://127.0.0.1:8002 SESSION_SECURE_COOKIE=false CACHE_STORE=array \
  PHP_CLI_SERVER_WORKERS=6 php artisan serve --port=8002 &

python3 tests/securite/sondes-anonymes.py http://127.0.0.1:8002     # 55 sondes
python3 tests/securite/limites.py                                    # sur un serveur aux limites réelles (8001)
node tests/securite/livewire-attaques.mjs                           # escalade via Livewire (19 sondes)
COOKIE="$(php artisan yewtod:dev-session superadmin@yewtod.test | tail -1)" node tests/securite/csp.mjs
php artisan tinker tests/securite/injecter-charges-xss.php && COOKIE="…" node tests/securite/xss-stocke.mjs
php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder   # remettre une base propre ensuite
```

Outils externes (installés à part) : `zap.sh -cmd -quickurl http://127.0.0.1:8002/ -quickout zap.xml`, `sqlmap -u "http://127.0.0.1:8002/works?q=a" --level 3 --risk 2`, `nuclei -u http://127.0.0.1:8002`, `nikto -h 127.0.0.1 -p 8002`.
