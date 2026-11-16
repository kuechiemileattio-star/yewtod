#!/usr/bin/env bash
# =====================================================================
# Retour arrière (cahier § 12.3) : remet le code de la version précédente
# et, si demandé, la base de la dernière sauvegarde.
#
#   scripts/retour-arriere.sh preprod|prod            # code seulement
#   scripts/retour-arriere.sh preprod|prod --base     # code + base
# =====================================================================
set -euo pipefail
ENV_NAME="${1:-}"; WITH_DB="${2:-}"
[[ "$ENV_NAME" == "preprod" || "$ENV_NAME" == "prod" ]] || { echo "Usage : $0 preprod|prod [--base]"; exit 1; }
cd "$(dirname "$0")/.."
# shellcheck disable=SC1091
source scripts/deploiement.env
P=$( [[ "$ENV_NAME" == "prod" ]] && echo PROD || echo PREPROD )
SSH_TARGET="${P}_SSH"; SSH_PORT="${P}_PORT"; DIR="${P}_DIR"; PHP="${P}_PHP"; COMPOSER="${P}_COMPOSER"
SSH_TARGET="${!SSH_TARGET}"; SSH_PORT="${!SSH_PORT}"; DIR="${!DIR}"; PHP="${!PHP}"; COMPOSER="${!COMPOSER}"
remote() { ssh -p "$SSH_PORT" "$SSH_TARGET" "cd '$DIR' && $*"; }

ARCHIVE="$(ssh -p "$SSH_PORT" "$SSH_TARGET" "ls -1t '$DIR'/../yewtod-versions/code-*.tar.gz 2>/dev/null | head -1")"
[[ -n "$ARCHIVE" ]] || { echo "Aucune archive de code trouvée."; exit 1; }
echo "Version à restaurer : $ARCHIVE"
read -r -p "Confirmer le retour arrière de $ENV_NAME ? [oui/non] " ok; [[ "$ok" == "oui" ]] || exit 1

remote "$PHP artisan down --retry=60 || true"
remote "tar -xzf '$ARCHIVE' -C . && $COMPOSER install --no-dev --optimize-autoloader --no-interaction"
if [[ "$WITH_DB" == "--base" ]]; then
  BACKUP="$(ssh -p "$SSH_PORT" "$SSH_TARGET" "ls -1t '$DIR'/storage/app/backups/yewtod-*.ybk | head -1")"
  echo "Restauration de la base depuis $BACKUP (la phrase de passe sera demandée)."
  ssh -t -p "$SSH_PORT" "$SSH_TARGET" "cd '$DIR' && $PHP artisan yewtod:backup:restore '$BACKUP' --importer"
fi
remote "$PHP artisan optimize:clear && $PHP artisan config:cache && $PHP artisan route:cache && $PHP artisan view:cache && $PHP artisan up"
echo "Retour arrière terminé."
