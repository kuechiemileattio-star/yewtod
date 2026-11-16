# Lot 10 — Déploiement sur le mutualisé LWS

## Ce qui a été fait

| Élément | Fichiers |
| --- | --- |
| Script de déploiement SSH en 10 étapes : vérifications (branche, arbre propre, tests, audits), build local, maintenance, **sauvegarde avant**, archive du code, rsync, Composer `--no-dev`, **confirmation avant migration**, caches, vérification, remise en ligne, contrôle `/up` | `scripts/deployer.sh`, `scripts/deploiement.env.example` |
| Retour arrière (code, et base sur demande) | `scripts/retour-arriere.sh` |
| Copie des sauvegardes hors de l'hébergeur | `scripts/recuperer-sauvegardes.sh` |
| Préproduction protégée par HTTP Basic (mot de passe haché) | `app/Http/Middleware/ProtectPreproduction.php` |
| Vérification de la configuration de production | `app/Console/Commands/CheckProduction.php` (`yewtod:verifier-production`) |
| Procédure complète : panel LWS, `.env`, cron, premier déploiement, retour arrière, restauration, liste de contrôle | `docs/DEPLOIEMENT.md` |

## Vérifier

```bash
php artisan test tests/Feature/Socle/DeploymentTest.php
php artisan yewtod:verifier-production     # en local : échecs attendus (debug, https…)
bash -n scripts/deployer.sh
```

Le déploiement réel n'a pas pu être exécuté depuis l'environnement de génération (pas d'accès à LWS) : il est à faire par Yann en suivant `docs/DEPLOIEMENT.md`.
