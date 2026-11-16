#!/usr/bin/env bash
# =====================================================================
# Déploiement Yewtod SS sur le mutualisé LWS (cahier § 12.3, décision 12)
#
#   scripts/deployer.sh preprod      # toujours en premier
#   scripts/deployer.sh prod
#
# À lancer depuis WSL, à la racine du projet, sur la branche v2 propre.
# Étapes : vérifications locales (tests, audits, build) → maintenance →
# SAUVEGARDE chiffrée → archive du code en place (retour arrière) → envoi
# rsync → composer --no-dev → CONFIRMATION puis migrations → caches →
# remise en ligne → contrôle de santé. En cas d'échec après la mise en
# maintenance, le site reste en maintenance : lancer scripts/retour-arriere.sh.
# =====================================================================
set -euo pipefail

ENV_NAME="${1:-}"
[[ "$ENV_NAME" == "preprod" || "$ENV_NAME" == "prod" ]] || { echo "Usage : $0 preprod|prod [--sans-tests]"; exit 1; }
SKIP_TESTS="${2:-}"
cd "$(dirname "$0")/.."
[[ -f scripts/deploiement.env ]] || { echo "scripts/deploiement.env manquant (copier scripts/deploiement.env.example)."; exit 1; }
# shellcheck disable=SC1091
source scripts/deploiement.env
P=$( [[ "$ENV_NAME" == "prod" ]] && echo PROD || echo PREPROD )
SSH_TARGET="${P}_SSH"; SSH_PORT="${P}_PORT"; DIR="${P}_DIR"; PHP="${P}_PHP"; COMPOSER="${P}_COMPOSER"; URL="${P}_URL"
SSH_TARGET="${!SSH_TARGET}"; SSH_PORT="${!SSH_PORT}"; DIR="${!DIR}"; PHP="${!PHP}"; COMPOSER="${!COMPOSER}"; URL="${!URL}"
SSH=(ssh -p "$SSH_PORT" "$SSH_TARGET")
remote() { "${SSH[@]}" "cd '$DIR' && $*"; }
step() { printf '\n\033[1;34m▶ %s\033[0m\n' "$*"; }

step "1/10 Vérifications locales"
[[ "$(git rev-parse --abbrev-ref HEAD)" == "v2" ]] || { echo "Déployez depuis la branche v2."; exit 1; }
[[ -z "$(git status --porcelain)" ]] || { echo "Arbre de travail non propre : committez d'abord."; exit 1; }
if [[ "$ENV_NAME" == "prod" ]]; then
  read -r -p "La préproduction a-t-elle été validée avec ce même commit ($(git rev-parse --short HEAD)) ? [oui/non] " ok
  [[ "$ok" == "oui" ]] || exit 1
fi
if [[ "$SKIP_TESTS" != "--sans-tests" ]]; then php artisan test --compact; fi
composer audit --no-dev
npm audit --omit=dev --audit-level=high

step "2/10 Compilation des assets (aucun Node en production)"
npm ci --no-audit --no-fund
npm run build

step "3/10 Mode maintenance"
"${SSH[@]}" "mkdir -p '$DIR'"
remote "[ -f artisan ] && $PHP artisan down --retry=60 || true"

step "4/10 Sauvegarde chiffrée AVANT toute modification"
remote "[ -f artisan ] && $PHP artisan yewtod:backup || echo 'Première installation : pas de sauvegarde.'"

step "5/10 Archive du code en place (retour arrière)"
STAMP="$(date +%Y%m%d-%H%M%S)"
remote "mkdir -p ../yewtod-versions && tar --exclude=./storage --exclude=./.env --exclude=./vendor --exclude=./public/storage -czf ../yewtod-versions/code-$STAMP.tar.gz . 2>/dev/null || true; ls -1t ../yewtod-versions/code-*.tar.gz 2>/dev/null | tail -n +6 | xargs -r rm -f"

step "6/10 Envoi du code (rsync)"
rsync -az --delete -e "ssh -p $SSH_PORT" \
  --exclude='.git/' --exclude='.env' --exclude='.env.*' --exclude='node_modules/' --exclude='vendor/' \
  --exclude='storage/' --exclude='public/storage' --exclude='public/hot' --exclude='tests/' --exclude='docs/captures/' \
  --exclude='scripts/deploiement.env' --exclude='.phpunit.cache/' \
  ./ "$SSH_TARGET:$DIR/"
# storage/ : uniquement l'arborescence et les .gitignore/.htaccess (jamais les fichiers du serveur).
rsync -az -e "ssh -p $SSH_PORT" --include='*/' --include='.gitignore' --include='.htaccess' --exclude='*' storage/ "$SSH_TARGET:$DIR/storage/"

step "7/10 Dépendances PHP (sans outils de développement)"
remote "$COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist && chmod 600 .env && chmod -R u+rwX storage bootstrap/cache"

step "8/10 Migrations (après confirmation)"
remote "$PHP artisan migrate:status --no-ansi | grep -i pending || echo 'Aucune migration en attente.'"
read -r -p "Appliquer les migrations ci-dessus sur $ENV_NAME ? (une sauvegarde vient d'être faite) [oui/non] " ok
if [[ "$ok" == "oui" ]]; then remote "$PHP artisan migrate --force"; else echo "Migrations NON appliquées."; fi

step "9/10 Lien de stockage, caches, vérification"
remote "[ -e public/storage ] || $PHP artisan storage:link"
remote "$PHP artisan optimize:clear && $PHP artisan config:cache && $PHP artisan route:cache && $PHP artisan view:cache && $PHP artisan event:cache"
remote "$PHP artisan yewtod:verifier-production" || echo "⚠ Vérification : voir les ÉCHECS ci-dessus (le site reste en maintenance)."

step "10/10 Remise en ligne et contrôle de santé"
remote "$PHP artisan up"
CODE="$(curl -s -o /dev/null -w '%{http_code}' "$URL/up" || true)"
echo "Santé $URL/up : HTTP $CODE"
[[ "$CODE" == "200" || "$CODE" == "401" ]] || { echo "⚠ Le site ne répond pas correctement : scripts/retour-arriere.sh $ENV_NAME"; exit 1; }
echo "Déploiement $ENV_NAME terminé ($(git rev-parse --short HEAD))."
