#!/usr/bin/env bash
# =====================================================================
# Déploiement Yewtod SS sur LWS Perso, SANS SSH (décisions D73, D77).
#
#   scripts/deployer-ftp.sh                    # déploie le commit courant (HEAD)
#   scripts/deployer-ftp.sh --env              # envoie aussi le .env de production
#   scripts/deployer-ftp.sh --sans-tests       # (déconseillé) saute les tests
#   scripts/deployer-ftp.sh <commit>           # RETOUR ARRIÈRE : redéploie un ancien commit
#
# Le FTP de LWS est bridé : envoyer les fichiers un par un est interminable.
# On envoie donc UNE archive du commit (+ son empreinte) et l'installateur ;
# l'installation se lance ensuite dans le Terminal web (pas de SSH) :
#   php _deploiement/installer.php <archive> <empreinte>
# Il met le site en maintenance, échange les dossiers (la version précédente
# est gardée pour un retour arrière instantané : installer.php --retour),
# sans jamais toucher au .env ni aux fichiers de storage/.
# =====================================================================
set -euo pipefail
cd "$(dirname "$0")/.."

REF="HEAD"; SEND_ENV="non"; SKIP_TESTS="non"
for arg in "$@"; do
  case "$arg" in
    --env) SEND_ENV="oui" ;;
    --sans-tests) SKIP_TESTS="oui" ;;
    -*) echo "Option inconnue : $arg"; exit 1 ;;
    *) REF="$arg" ;;
  esac
done

[[ -f scripts/deploiement-ftp.env ]] || { echo "scripts/deploiement-ftp.env manquant (copier scripts/deploiement-ftp.env.example)."; exit 1; }
# shellcheck disable=SC1091
source scripts/deploiement-ftp.env
command -v lftp >/dev/null || { echo "lftp manquant : sudo apt install lftp"; exit 1; }

step() { printf '\n\033[1;34m▶ %s\033[0m\n' "$*"; }
ask() { local r; printf '%s [oui/non] ' "$1"; read -r r; [[ "$r" == "oui" ]]; }
terminal() { printf '\n\033[1;33m   À coller dans le Terminal web LWS, UNE ligne à la fois :\033[0m\n'; printf '     %s\n' "$@"; }

read -r -s -p "Mot de passe FTP de $FTP_UTILISATEUR : " LFTP_PASSWORD; echo
export LFTP_PASSWORD
# shellcheck source=scripts/lib-ftp.sh
source scripts/lib-ftp.sh
ftp_preparer
D="${FTP_DOSSIER%/}"   # « / » → « » : chemins du type $D/_deploiement

step "1/6 Vérifications locales"
SHA="$(git rev-parse --short "$REF")"
if [[ "$REF" == "HEAD" ]]; then
  [[ "$(git rev-parse --abbrev-ref HEAD)" == "v2" ]] || { echo "Déployez depuis la branche v2."; exit 1; }
  [[ -z "$(git status --porcelain)" ]] || { echo "Arbre de travail non propre : committez d'abord (seul un commit est déployé)."; exit 1; }
  [[ "$SKIP_TESTS" == "oui" ]] || php artisan test --compact
  composer audit --no-dev
  npm audit --omit=dev --audit-level=high
else
  echo "RETOUR ARRIÈRE vers $SHA ($(git log -1 --format='%s' "$REF"))."
  ask "Confirmer le redéploiement de cet ancien commit ?" || exit 1
fi

step "2/6 État du serveur"
# Seuls des fichiers visibles sont testés : beaucoup de serveurs FTP masquent les fichiers « . » (.env).
REMOTE_LIST="$(ftp_run "cd '${D:-/}'; cls -1 artisan; cls -1 database/migrations/" 2>/dev/null || true)"
FIRST_INSTALL="oui"; grep -qx 'artisan' <<<"$REMOTE_LIST" && FIRST_INSTALL="non"
echo "Première installation : $FIRST_INSTALL"

step "3/6 Paquet et archive du commit $SHA"
scripts/preparer-paquet.sh "$REF"
NEW_MIGRATIONS="$(comm -23 <(ls build/paquet/database/migrations | sort) <(grep -o '[^/]*\.php$' <<<"$REMOTE_LIST" | sort) || true)"
if [[ -n "$NEW_MIGRATIONS" ]]; then echo "Nouvelles migrations :"; sed 's/^/  - /' <<<"$NEW_MIGRATIONS"; else echo "Aucune nouvelle migration."; fi
ARCHIVE="yewtod-$SHA.zip"
INFO="$(php scripts/creer-archive.php build/paquet "build/$ARCHIVE")"
echo "Archive : $INFO"
EMPREINTE="$(sed -n 's/.*sha256 \([a-f0-9]\{64\}\).*/\1/p' <<<"$INFO")"
[[ -n "$EMPREINTE" ]] || { echo "Empreinte de l'archive introuvable."; exit 1; }
printf '# Généré par deployer-ftp.sh : jamais servi par le web (D77).\nRequire all denied\n' > build/deploiement.htaccess

step "4/6 Envoi de l'archive (le site en ligne n'est pas encore modifié)"
ftp_run "cd '${D:-/}'; mkdir -p _deploiement; put build/deploiement.htaccess -o _deploiement/.htaccess; put scripts/installer-version.php -o _deploiement/installer.php; put build/$ARCHIVE -o _deploiement/$ARCHIVE"
echo "Archive et installateur envoyés dans _deploiement/."
if [[ "$SEND_ENV" == "oui" || "$FIRST_INSTALL" == "oui" ]]; then
  # Le .env n'est envoyé qu'à la première installation ou sur demande (--env) : jamais écrasé par surprise.
  [[ -f "$ENV_LOCAL" ]] || { echo "Fichier $ENV_LOCAL introuvable (voir docs/DEPLOIEMENT.md, étape .env)."; exit 1; }
  grep -q '^APP_KEY=base64:' "$ENV_LOCAL" || { echo "APP_KEY absente de $ENV_LOCAL."; exit 1; }
  ftp_run "cd '${D:-/}'; put '$ENV_LOCAL' -o .env; chmod 600 .env"
  echo ".env envoyé (droits 600)."
fi

step "5/6 Installation dans le Terminal web"
CMDS=()
[[ "$FIRST_INSTALL" == "non" && -n "$NEW_MIGRATIONS" ]] && CMDS+=("php artisan yewtod:backup")
CMDS+=("php _deploiement/installer.php $ARCHIVE $EMPREINTE")
if [[ "$FIRST_INSTALL" == "oui" ]]; then
  CMDS+=("php artisan migrate --force --seed" "php artisan yewtod:create-super-admin")
elif [[ -n "$NEW_MIGRATIONS" ]]; then
  CMDS+=("php artisan migrate --force")
fi
CMDS+=("php artisan optimize:clear" "php artisan optimize" "php artisan yewtod:verifier-production" "php artisan up")
terminal "${CMDS[@]}"
echo
echo "   En cas de problème après l'installation : php _deploiement/installer.php --retour"
ask "Ces commandes ont-elles été lancées sans erreur ?" || { echo "Corrigez, ou revenez en arrière (installer.php --retour). Rien d'autre à faire côté WSL."; exit 1; }

step "6/6 Contrôle de santé"
code="$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL/up" || true)"
home="$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL/" || true)"
echo "/up → $code ; accueil → $home (401 normal si la protection de préproduction est active)"
[[ "$code" == "200" ]] || { echo "⚠ Le site ne répond pas correctement : vérifiez storage/logs, ou revenez en arrière (installer.php --retour)."; exit 1; }
echo "Commit $SHA en ligne."
