-- =====================================================================
-- Yewtod SS — création de la base et des comptes MariaDB (LOCAL uniquement)
-- =====================================================================
-- Pourquoi deux comptes ? Principe du moindre privilège (cahier § 8.6) :
--   * yewtod_app     : utilisé par le site. Il ne peut que lire et écrire des
--                      lignes (SELECT/INSERT/UPDATE/DELETE). Une injection SQL
--                      qui passerait malgré tout ne pourrait ni supprimer une
--                      table, ni créer un utilisateur, ni lire un fichier.
--   * yewtod_migrate : utilisé uniquement pendant `php artisan migrate`
--                      (connexion Laravel « migrate »). Droits de structure.
--   * yewtod_test    : base de tests isolée (Pest), droits complets sur
--                      yewtod_test seulement.
-- Jamais root dans l'application.
--
-- Usage : remplacez les trois placeholders par des mots de passe longs et
-- aléatoires (ex. `openssl rand -base64 32`), puis :
--     sudo mariadb < database/sql/creer-utilisateurs.sql
-- Recopiez ensuite ces mots de passe dans .env (droits 600), nulle part ailleurs.
--
-- Chez LWS (mutualisé), le panel ne permet qu'UN utilisateur par base : ce
-- script ne s'y applique pas. Voir docs/SECURITE.md (« utilisateur unique »).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS yewtod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS yewtod_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Comptes limités à localhost : aucune connexion distante possible.
CREATE USER IF NOT EXISTS 'yewtod_app'@'localhost'     IDENTIFIED BY '<MOT_DE_PASSE_APP>';
CREATE USER IF NOT EXISTS 'yewtod_migrate'@'localhost' IDENTIFIED BY '<MOT_DE_PASSE_MIGRATE>';
CREATE USER IF NOT EXISTS 'yewtod_test'@'localhost'    IDENTIFIED BY '<MOT_DE_PASSE_TEST>';

-- Site : uniquement la manipulation de lignes.
GRANT SELECT, INSERT, UPDATE, DELETE ON yewtod.* TO 'yewtod_app'@'localhost';

-- Migrations : structure + données (les seeders de référence tournent aussi
-- à la suite de `migrate --seed`). Pas de GRANT OPTION, pas de FILE, pas de SUPER.
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES,
      CREATE VIEW, SHOW VIEW, LOCK TABLES
   ON yewtod.* TO 'yewtod_migrate'@'localhost';

-- Tests : base jetable.
GRANT ALL PRIVILEGES ON yewtod_test.* TO 'yewtod_test'@'localhost';

FLUSH PRIVILEGES;
