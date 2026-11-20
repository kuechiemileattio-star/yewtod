# Déploiement sur l'hébergement mutualisé LWS

Cadre (décision 12) : SSH, Composer, PHP 8.3+, cron à la minute, racine web modifiable, **pas** de worker permanent, **pas** de Node en production. Les assets sont compilés dans WSL et envoyés par `rsync` ; la file d'attente `database` est vidée par le cron (`schedule:run`).

Ordre : **préproduction d'abord**, validation, puis production avec le **même commit**.

## 1. Préparer chaque environnement dans le panel LWS (une fois)

1. **PHP** : version 8.3 ou plus récente, extensions `pdo_mysql`, `mbstring`, `intl`, `gd`, `zip`, `fileinfo`, `sodium`, `openssl`. Vérifier en SSH : `php -v && php -m`. Noter le chemin exact du binaire (`which php`, ou `/usr/local/php8.3/bin/php` selon l'offre) : il sert au cron et à `scripts/deploiement.env`.
2. **Accès SSH** : l'activer, puis déposer votre clé publique (`~/.ssh/id_ed25519.pub` de WSL) dans l'espace client. Tester : `ssh utilisateur@ssh.clusterXXX.lws.fr`.
3. **Base de données** : créer la base et son utilisateur (un seul par base chez LWS, voir `docs/SECURITE.md` § 2). Relever la version : phpMyAdmin → `SELECT VERSION();` — votre MariaDB locale doit être de la même version majeure (`docs/INSTALLATION.md` § 1.2). Désactiver tout accès distant à la base.
4. **Domaine** : préproduction sur `preprod.yewtod.com`, production sur `yewtod.com`. Faire pointer la **racine web** sur le dossier `public/` de l'application (ex. `/home/utilisateur/yewtod.com/public`). Activer le certificat **SSL** (Let's Encrypt) et la redirection HTTPS.
5. **DNS** (lors du transfert du domaine, décision 11) : enregistrements `A`/`AAAA` vers l'hébergement LWS, `CNAME preprod` ; pour les emails, enregistrements **SPF**, **DKIM** et **DMARC** fournis par LWS (ou par le service SMTP choisi) ; pour Brevo, les enregistrements d'authentification du domaine expéditeur.

## 2. Fichier `.env` du serveur (une fois par environnement)

Créé à la main en SSH dans le dossier de l'application, droits `600`, **jamais** dans Git :

```dotenv
APP_NAME="Yewtod SS"
APP_ENV=production
APP_KEY=                       # php artisan key:generate --show (en local), puis coller ici
APP_DEBUG=false
APP_URL=https://yewtod.com     # https://preprod.yewtod.com en préproduction
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mariadb
DB_HOST=…                      # indiqué par LWS
DB_PORT=3306
DB_DATABASE=…
DB_USERNAME=…
DB_PASSWORD='…'
DB_MIGRATE_USERNAME=           # vide chez LWS : utilisateur unique
DB_MIGRATE_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=30
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp               # SMTP LWS ou service transactionnel
MAIL_HOST=…
MAIL_PORT=587
MAIL_USERNAME=…
MAIL_PASSWORD='…'
MAIL_FROM_ADDRESS="no-reply@yewtod.com"

BREVO_API_KEY=                 # facultatif : la synchronisation se désactive sans clé
BREVO_LIST_ID=
TURNSTILE_SITE_KEY=            # facultatif mais recommandé en production
TURNSTILE_SECRET_KEY=
HIBP_ENABLED=true

BACKUP_PASSPHRASE='…'          # 32+ caractères aléatoires, distincte d'APP_KEY

LEGAL_PUBLISHER="…"            # mentions légales obligatoires
LEGAL_ADDRESS="…"
LEGAL_DIRECTOR="…"
LEGAL_CONTACT_EMAIL="…"

# Préproduction UNIQUEMENT (laisser vide en production) :
PREPROD_USER=
PREPROD_PASSWORD_HASH=
```

> Conservez `APP_KEY` et `BACKUP_PASSPHRASE` dans votre gestionnaire de mots de passe : sans `APP_KEY`, les secrets 2FA, les sessions et les descriptions chiffrées sont perdus ; sans `BACKUP_PASSPHRASE`, les sauvegardes sont illisibles.

En préproduction, protégez l'accès (cahier § 12.3) :

```bash
php -r 'echo password_hash("un mot de passe long", PASSWORD_ARGON2ID), PHP_EOL;'
# puis dans .env : PREPROD_USER=relecture  et  PREPROD_PASSWORD_HASH='$argon2id$…'
```

## 3. Cron (une fois par environnement)

Dans le panel LWS (« Tâches planifiées / Cron »), **une seule** ligne, chaque minute :

```cron
* * * * * /usr/local/php8.3/bin/php /home/utilisateur/yewtod.com/artisan schedule:run >> /dev/null 2>&1
```

Ce cron lance : la bascule des contenus programmés (chaque minute), la file d'attente (`queue:work --stop-when-empty`, chaque minute), la sauvegarde chiffrée (2 h 30), les purges RGPD (3 h 15). Vérifier : `php artisan schedule:list`.

## 4. Configurer le script de déploiement (une fois, dans WSL)

```bash
cp scripts/deploiement.env.example scripts/deploiement.env   # ignoré par Git
nano scripts/deploiement.env                                  # hôtes SSH, dossiers, binaire PHP, URL
```

## 5. Premier déploiement

```bash
scripts/deployer.sh preprod
```

Puis, en SSH sur le serveur :

```bash
cd ~/preprod.yewtod.com
php artisan migrate --force          # si vous avez répondu « non » pendant le script
php artisan db:seed --class=ReferenceSeeder --force   # rôles, permissions, réglages (jamais de démo en ligne)
php artisan yewtod:create-super-admin                 # premier Super Admin, interactif
php artisan yewtod:verifier-production                # doit tout afficher « OK » (sauf options désactivées)
```

Connectez-vous, activez la 2FA, relisez le site. **Testez une restauration** une fois avant la mise en ligne (voir § 7).

## 6. Déploiements suivants

```bash
git switch v2 && git pull
scripts/deployer.sh preprod     # puis validation
scripts/deployer.sh prod        # même commit
```

Le script refuse un arbre non propre ou une autre branche, lance les tests, `composer audit` et `npm audit`, compile les assets, met le site en maintenance, **sauvegarde**, archive le code en place, envoie les fichiers, installe les dépendances sans outils de développement, **demande confirmation** avant les migrations, régénère les caches, vérifie la configuration, remet en ligne et contrôle `/up`.

## 7. Retour arrière et restauration

```bash
scripts/retour-arriere.sh prod            # code de la version précédente
scripts/retour-arriere.sh prod --base     # code + base de la dernière sauvegarde
```

Restauration manuelle d'une sauvegarde donnée :

```bash
php artisan yewtod:backup:restore storage/app/backups/yewtod-AAAAMMJJ-HHMMSS.ybk --dossier=/tmp/restau            # vérifier
php artisan yewtod:backup:restore storage/app/backups/yewtod-AAAAMMJJ-HHMMSS.ybk --dossier=/tmp/restau --importer # écraser la base
cp -r /tmp/restau/fichiers/private/* storage/app/private/ && cp -r /tmp/restau/fichiers/public/* storage/app/public/
```

**Test de restauration avant la mise en ligne** : sur la préproduction, créez une seconde base vide dans le panel, puis lancez la restauration avec `DB_DATABASE=… DB_USERNAME=… DB_PASSWORD=… php artisan yewtod:backup:restore … --importer`, comparez le nombre de lignes des tables principales, supprimez la base de test. Notez la date dans `docs/SECURITE.md` § 3.

## 8. Sauvegardes hors de l'hébergeur

Chaque semaine, depuis WSL :

```bash
scripts/recuperer-sauvegardes.sh prod      # copie dans ~/sauvegardes-yewtod/prod (chiffrées)
```

Les sauvegardes de LWS (offre) s'ajoutent à celles-ci mais ne les remplacent pas. Ne copiez jamais la base de production vers votre environnement de développement (cahier § 8.6).

## 9. Liste de contrôle avant la mise en ligne

- [ ] `php artisan yewtod:verifier-production` : aucun échec.
- [ ] `APP_DEBUG=false`, HTTPS forcé, certificat valide, en-têtes vérifiés (https://securityheaders.com).
- [ ] Mentions légales complètes (éditeur, directeur de publication, hébergeur à jour).
- [ ] SMTP testé (invitation envoyée et reçue), SPF/DKIM/DMARC en place.
- [ ] Turnstile et Brevo configurés (ou volontairement désactivés).
- [ ] Premier Super Admin créé, 2FA activée, codes de secours rangés.
- [ ] Cron actif (`schedule:list`), une sauvegarde nocturne présente, restauration testée.
- [ ] Préproduction protégée (`PREPROD_USER`), production **sans** protection.
- [ ] `composer audit` et `npm audit` sans vulnérabilité haute.
