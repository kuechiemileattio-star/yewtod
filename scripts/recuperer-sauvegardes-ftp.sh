#!/usr/bin/env bash
# Copie hors de l'hébergeur des sauvegardes chiffrées, par FTP (formule LWS Perso, D73).
# À lancer chaque semaine depuis WSL : scripts/recuperer-sauvegardes-ftp.sh [dossier]
# Sans suppression : la copie locale garde aussi les sauvegardes que la rotation a retirées du serveur.
set -euo pipefail
cd "$(dirname "$0")/.."
# shellcheck disable=SC1091
source scripts/deploiement-ftp.env
DEST="${1:-$HOME/sauvegardes-yewtod}"
mkdir -p "$DEST" && chmod 700 "$DEST"
read -r -s -p "Mot de passe FTP de $FTP_UTILISATEUR : " LFTP_PASSWORD; echo
export LFTP_PASSWORD
SSL_OPTS="set ftp:ssl-force true; set ftp:ssl-protect-data true; set ssl:verify-certificate true;"
[[ "${FTP_TLS:-oui}" == "oui" ]] || SSL_OPTS="set ftp:ssl-allow false;"
lftp -e "$SSL_OPTS mirror --only-newer --no-perms --include-glob 'yewtod-*.ybk' '$FTP_DOSSIER/storage/app/backups' '$DEST'; bye" --env-password -u "$FTP_UTILISATEUR" "$FTP_HOTE"
chmod 600 "$DEST"/*.ybk 2>/dev/null || true
echo "Sauvegardes copiées dans $DEST :"; ls -1t "$DEST" | head -5
echo "Elles sont chiffrées : gardez BACKUP_PASSPHRASE et APP_KEY dans votre gestionnaire de mots de passe."
