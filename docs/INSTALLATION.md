# Installation locale (Windows + WSL Debian, sans Docker)

Ce guide installe tout ce qu'il faut pour faire tourner Yewtod SS dans WSL Debian : PHP 8.3+, Composer, MariaDB (même version majeure que LWS), puis le projet lui-même. Chaque étape dit **ce que fait la commande** et **comment vérifier** qu'elle a marché.

> Node 24 est déjà installé chez toi : il ne sert qu'à compiler les assets (Tailwind, JavaScript). Il n'est jamais utilisé en production.

---

## 1. Connaître la version de Debian et celle de LWS

### 1.1 Debian dans WSL

```bash
cat /etc/os-release | grep -E 'VERSION_CODENAME|VERSION_ID'
```

- `bookworm` = Debian 12, `trixie` = Debian 13. Note le nom : il sert pour les dépôts ci-dessous.

### 1.2 Version de MariaDB chez LWS (décision 12)

1. Espace client LWS → ton hébergement → **phpMyAdmin**.
2. Onglet **SQL**, exécute :

   ```sql
   SELECT VERSION();
   ```

3. Tu obtiens par exemple `10.11.9-MariaDB` ou `10.6.18-MariaDB`. Retiens la **version majeure** (`10.11`, `10.6`, `11.4`…).

Pourquoi : une fonction SQL qui marche en local mais pas chez LWS ferait planter la mise en ligne. En installant la même version majeure, tu testes sur le même moteur.

Note aussi la version de PHP proposée par LWS (panel → « Configuration PHP ») : vise la même en local (8.3 ou 8.4).

---

## 2. PHP 8.3+ et ses extensions

### 2.1 Dépôt PHP

- **Debian 13 (trixie)** fournit PHP 8.4 : passe directement au § 2.2 en remplaçant `8.3` par `8.4`.
- **Debian 12 (bookworm)** fournit PHP 8.2, trop ancien. On ajoute le dépôt de référence d'Ondřej Surý (mainteneur des paquets PHP Debian) :

```bash
sudo apt update
sudo apt install -y ca-certificates curl gnupg lsb-release
curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb
sudo dpkg -i /tmp/debsuryorg-archive-keyring.deb
echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
  | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update
```

- La clé vérifie la signature des paquets (on n'installe rien de non signé).

### 2.2 Installer PHP et les extensions

```bash
sudo apt install -y php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip \
  php8.3-mysql php8.3-gd php8.3-intl php8.3-bcmath php8.3-sqlite3 unzip git
```

| Extension | Utilité dans le projet |
| --- | --- |
| `mysql` (pdo_mysql) | Connexion à MariaDB |
| `gd` | Conversion des images en WebP, miniatures |
| `mbstring`, `intl` | Textes en français (accents, slugs) |
| `xml`, `curl`, `zip` | Laravel, Composer, appels Gemini/Brevo/Turnstile |

**Vérifier :**

```bash
php -v                      # doit afficher PHP 8.3.x (ou 8.4.x)
php -m | grep -E 'pdo_mysql|gd|intl|mbstring|sodium'   # 5 lignes attendues
php -r 'echo PASSWORD_ARGON2ID, PHP_EOL;'               # « argon2id » (hachage des mots de passe)
```

---

## 3. Composer

```bash
cd /tmp
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
# Vérifie l'empreinte publiée sur https://composer.github.io/pubkeys.html avant d'exécuter :
php -r "echo hash_file('sha384', 'composer-setup.php'), PHP_EOL;"
php composer-setup.php --install-dir=/tmp
sudo mv /tmp/composer.phar /usr/local/bin/composer
rm composer-setup.php
```

- La vérification de l'empreinte garantit que l'installeur n'a pas été modifié.

**Vérifier :** `composer --version` → `Composer version 2.x`.

---

## 4. MariaDB (même version majeure que LWS)

### 4.1 Installer

- Si la version de Debian correspond à LWS (Debian 12 → 10.11, Debian 13 → 11.8), le paquet Debian suffit :

  ```bash
  sudo apt install -y mariadb-server mariadb-client
  ```

- Sinon, utilise le **dépôt officiel MariaDB** en fixant la version majeure relevée chez LWS (exemple pour 10.6) :

  ```bash
  curl -LsSO https://r.mariadb.com/downloads/mariadb_repo_setup
  # Vérifie la somme indiquée sur https://mariadb.com/kb/en/mariadb-package-repository-setup-and-usage/
  sha256sum mariadb_repo_setup
  chmod +x mariadb_repo_setup
  sudo ./mariadb_repo_setup --mariadb-server-version="mariadb-10.6"
  sudo apt update
  sudo apt install -y mariadb-server mariadb-client
  ```

Démarrer le service (WSL n'utilise pas toujours systemd) :

```bash
sudo service mariadb start      # ou : sudo systemctl start mariadb
```

**Vérifier :**

```bash
sudo mariadb -e "SELECT VERSION();"     # même majeure que LWS
```

### 4.2 Sécuriser

```bash
sudo mariadb-secure-installation
```

Réponses conseillées : authentification `unix_socket` pour root (garder), **supprimer les utilisateurs anonymes**, **interdire root à distance**, **supprimer la base `test`**, recharger les privilèges.

Écoute locale uniquement (cahier § 8.6) :

```bash
grep -R "bind-address" /etc/mysql/ 2>/dev/null
# Doit afficher bind-address = 127.0.0.1. Sinon, l'ajouter dans
# /etc/mysql/mariadb.conf.d/50-server.cnf, section [mysqld], puis :
sudo service mariadb restart
sudo mariadb -e "SHOW VARIABLES LIKE 'bind_address';"    # 127.0.0.1
```

### 4.3 Créer la base et les comptes

Le script `database/sql/creer-utilisateurs.sql` crée les bases `yewtod` et `yewtod_test` et trois comptes :

| Compte | Droits | Utilisé par |
| --- | --- | --- |
| `yewtod_app` | SELECT, INSERT, UPDATE, DELETE sur `yewtod` | le site (`DB_USERNAME`) |
| `yewtod_migrate` | + CREATE, ALTER, DROP, INDEX, REFERENCES… sur `yewtod` | les migrations (`DB_MIGRATE_USERNAME`) |
| `yewtod_test` | tout, mais seulement sur `yewtod_test` | les tests Pest (`.env.testing`) |

```bash
cd ~/yewtod                              # ton clone (voir § 5)
for v in APP MIGRATE TEST; do echo "$v=$(openssl rand -hex 24)"; done > ~/.yewtod-db-pass
chmod 600 ~/.yewtod-db-pass && cat ~/.yewtod-db-pass
source ~/.yewtod-db-pass
sed -e "s/<MOT_DE_PASSE_APP>/$APP/" -e "s/<MOT_DE_PASSE_MIGRATE>/$MIGRATE/" -e "s/<MOT_DE_PASSE_TEST>/$TEST/" \
    database/sql/creer-utilisateurs.sql | sudo mariadb
```

- Les mots de passe sont générés aléatoirement et ne sont jamais écrits dans le dépôt.

**Vérifier :**

```bash
sudo mariadb -e "SHOW GRANTS FOR 'yewtod_app'@'localhost';"
# GRANT SELECT, INSERT, UPDATE, DELETE ON `yewtod`.* ...   (et rien d'autre)
mariadb -u yewtod_app -p"$APP" yewtod -e "CREATE TABLE t (id int);"
# ERROR 1142 ... CREATE command denied  ← c'est voulu
```

---

## 5. Récupérer la branche `v2` et la pousser

Le projet t'est livré sous deux formes : une archive `.zip` et un **`git bundle`** (`yewtod-v2.bundle`), qui contient tout l'historique des commits.

```bash
# 1. Ton clone existant du dépôt (ne touche pas à main)
git clone https://github.com/kuechiemileattio-star/yewtod.git ~/yewtod   # si pas déjà fait
cd ~/yewtod

# 2. Vérifier le bundle puis importer la branche v2 (branche orpheline :
#    elle ne partage aucun historique avec main, c'est voulu)
git bundle verify ~/Téléchargements/yewtod-v2.bundle
git fetch ~/Téléchargements/yewtod-v2.bundle v2:v2
git switch v2

# 3. Vérifier l'auteur des commits (décision 15)
git log --format='%h %an <%ae>' | head
#   Si l'email n'est pas celui lié à ton compte GitHub yann0511 :
#   git rebase --root --exec 'git commit --amend --no-edit --reset-author'
#   (après avoir fait git config user.email "<ton email GitHub>")

# 4. Pousser (avec ton compte yann0511)
git push -u origin v2
```

> Dans WSL, le dossier Téléchargements de Windows est sous `/mnt/c/Users/<toi>/Downloads/`.

### 5.1 Dépendances et configuration

```bash
cd ~/yewtod
composer update            # génère composer.lock (décision D3) — à committer ensuite
npm ci || npm install
cp .env.example .env && chmod 600 .env
php artisan key:generate
```

Renseigne ensuite dans `.env` :

```dotenv
DB_USERNAME=yewtod_app
DB_PASSWORD=<valeur APP de ~/.yewtod-db-pass>
DB_MIGRATE_USERNAME=yewtod_migrate
DB_MIGRATE_PASSWORD=<valeur MIGRATE>
BACKUP_PASSPHRASE=<openssl rand -base64 48>
```

Et crée `.env.testing` (ignoré par Git) pour les tests :

```bash
printf "APP_KEY=base64:%s\nDB_USERNAME=yewtod_test\nDB_PASSWORD=%s\n" "$(openssl rand -base64 32)" "$TEST" > .env.testing
chmod 600 .env.testing
```

```bash
sed -i '/composer.lock/d' .gitignore      # retire l'exclusion temporaire (décision D3)
git add .gitignore composer.lock && git commit -m "Ajoute composer.lock généré depuis Packagist"
```

---

## 6. Lancer le projet

```bash
php artisan migrate --seed          # tables + rôles, permissions, réglages
php artisan db:seed --class=DemoSeeder   # données de démonstration (local uniquement)
php artisan yewtod:create-super-admin    # premier Super Admin (interactif)
npm run build                       # ou « npm run dev » pendant le développement
php artisan serve                   # http://localhost:8000
```

Dans un second terminal, pour la file d'attente et les tâches planifiées (comme le cron de LWS) :

```bash
php artisan schedule:work
```

**Vérifier :**

```bash
php artisan test                    # tous les tests doivent être verts
vendor/bin/pint --test              # style
vendor/bin/phpstan analyse          # analyse statique (niveau 6)
```

- Ouvre http://localhost:8000 : le site public s'affiche.
- http://localhost:8000/login : connexion ; la double authentification sera exigée.

### 6.1 Tester chaque rôle avec les comptes de démonstration

Le `DemoSeeder` crée un compte par rôle, avec le mot de passe commun **`Demo-Yewtod-Local-2026!`** (local uniquement) :

| Email | Rôle |
| --- | --- |
| `superadmin@yewtod.test` | Super Admin |
| `admin@yewtod.test` | Administrateur |
| `editeur@yewtod.test` | Éditeur |
| `contributeur@yewtod.test` | Contributeur |
| `collaborations@yewtod.test` | Modérateur des collaborations |

Leur double authentification est déjà configurée. Pour obtenir le code à 6 chiffres au moment de la connexion :

```bash
php artisan yewtod:code-demo editeur@yewtod.test     # « Code actuel : 123456 », valable ~30 s
```

(Commande refusée hors environnement local et pour tout compte qui n'est pas `@yewtod.test`.)

---

## 7. Problèmes fréquents

| Symptôme | Cause probable | Solution |
| --- | --- | --- |
| `SQLSTATE[HY000] [2002] Connection refused` | MariaDB arrêté (WSL redémarré) | `sudo service mariadb start` |
| `CREATE command denied` pendant `migrate` | `DB_MIGRATE_USERNAME` vide en local | renseigner le compte `yewtod_migrate` |
| Page blanche, erreur Vite manifest | assets non compilés | `npm run build` |
| Emails introuvables | `MAIL_MAILER=log` | lire `storage/logs/laravel.log` |
