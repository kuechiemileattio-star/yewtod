# Lot 00 — Socle technique

## Ce qui a été fait

- Squelette **Laravel 13.42** (PHP ^8.3), branche Git **`v2` orpheline** (aucun fichier ni historique React).
- Front : Tailwind 4 (variante `dark:`), Livewire 4 + Alpine (build CSP), compilation Vite **en local uniquement**.
- Qualité : Pest 4, Pint (preset Laravel), Larastan niveau 6 (`phpstan.neon`).
- Base : driver `mariadb`, mode strict, `utf8mb4`, UTC, requêtes préparées natives (`PDO::ATTR_EMULATE_PREPARES=false`).
- **Deux connexions** : `mariadb` (application) et `migrate` (structure), avec repli automatique sur l'utilisateur unique si `DB_MIGRATE_USERNAME` est vide (cas LWS).
- Script `database/sql/creer-utilisateurs.sql` : comptes `yewtod_app` (CRUD), `yewtod_migrate` (structure), `yewtod_test`.
- Middleware `SecurityHeaders` : CSP avec nonce, `frame-ancestors 'none'`, HSTS (HTTPS), `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `nosniff`.
- `AppServiceProvider` : `Model::shouldBeStrict()` hors production, `DB::prohibitDestructiveCommands()` en production, bascule sur la connexion `migrate` pour les commandes de structure.
- Hachage **Argon2id** ; `.env.example` complet (Gemini, Brevo, Turnstile, SMTP, sauvegardes) sans aucun secret.

## Choix techniques expliqués

- **Pourquoi deux connexions ?** Si une injection SQL passait malgré Eloquent, le compte `yewtod_app` ne pourrait ni `DROP TABLE`, ni `CREATE USER`, ni lire un fichier. Les droits de structure ne servent qu'au moment d'une migration.
- **Comment la bascule fonctionne ?** Un écouteur sur `CommandStarting` change la connexion par défaut pour `migrate`, `migrate:fresh`, etc. Le site, lui, n'utilise jamais `migrate`.
- **Pourquoi `EMULATE_PREPARES=false` ?** Avec l'émulation, PDO construit lui-même la chaîne SQL ; sans elle, le serveur reçoit la requête et les valeurs séparément : les valeurs ne peuvent jamais être interprétées comme du SQL.
- **Pourquoi une CSP avec nonce ?** Même si un texte malveillant échappait à l'échappement Blade, un `<script>` injecté n'aurait pas le nonce (aléatoire, différent à chaque requête) et le navigateur refuserait de l'exécuter.
- **Proxys de confiance** : aucun par défaut (voir D6) pour que l'IP utilisée par la limitation de débit ne soit pas falsifiable.

## Menaces traitées

| Menace | Protection |
| --- | --- |
| Injection SQL (défense en profondeur) | Compte CRUD seulement, requêtes préparées natives |
| XSS | CSP stricte avec nonce |
| Clickjacking | `frame-ancestors 'none'` + `X-Frame-Options: DENY` |
| Fuite d'informations | `X-Powered-By` retiré, `APP_DEBUG=false` en ligne |
| Destruction accidentelle | `prohibitDestructiveCommands` en production |

## Vérifier

```bash
php artisan test --filter=Socle        # en-têtes de sécurité
php artisan test tests/Unit/DatabaseConfigTest.php
curl -sI http://localhost:8000 | grep -iE 'content-security|x-frame|referrer'
```
