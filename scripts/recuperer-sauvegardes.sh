#!/usr/bin/env bash
# Copie hors de l'hébergeur des sauvegardes chiffrées (cahier § 8.6).
# À lancer chaque semaine depuis WSL : scripts/recuperer-sauvegardes.sh prod [dossier]
set -euo pipefail
ENV_NAME="${1:-prod}"; DEST="${2:-$HOME/sauvegardes-yewtod/$ENV_NAME}"
cd "$(dirname "$0")/.."
# shellcheck disable=SC1091
source scripts/deploiement.env
P=$( [[ "$ENV_NAME" == "prod" ]] && echo PROD || echo PREPROD )
SSH_TARGET="${P}_SSH"; SSH_PORT="${P}_PORT"; DIR="${P}_DIR"
SSH_TARGET="${!SSH_TARGET}"; SSH_PORT="${!SSH_PORT}"; DIR="${!DIR}"
mkdir -p "$DEST" && chmod 700 "$DEST"
# Sans --delete : la copie locale garde aussi les sauvegardes que la rotation a retirées du serveur.
rsync -av -e "ssh -p $SSH_PORT" "$SSH_TARGET:$DIR/storage/app/backups/" "$DEST/" --include='yewtod-*.ybk' --exclude='*'
echo "Sauvegardes copiées dans $DEST (chiffrées : gardez BACKUP_PASSPHRASE et APP_KEY dans votre gestionnaire de mots de passe)."
