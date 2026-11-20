#!/usr/bin/env bash
# =====================================================================
# Déploiement Yewtod SS sur LWS Perso, SANS SSH (décision D73).
#
#   scripts/deployer-ftp.sh                    # déploie le commit courant (HEAD)
#   scripts/deployer-ftp.sh --env              # envoie aussi le .env de production
#   scripts/deployer-ftp.sh --sans-tests       # (déconseillé) saute les tests
#   scripts/deployer-ftp.sh <commit>           # RETOUR ARRIÈRE : redéploie un ancien commit
#
# Ce qui est automatique (depuis WSL) : tests, paquet, mise en maintenance,
# envoi FTP, contrôle de santé. Ce qui reste à taper dans le Terminal web
# du panneau LWS (pas de SSH sur cette formule) : sauvegarde si des
# migrations arrivent, migrations, caches, vérification, remise en ligne.
# Le script s'arrête et affiche ces commandes au bon moment.
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
terminal() { printf '\n\033[1;33m   À taper dans le Terminal web LWS (dans le dossier du site) :\033[0m\n'; printf '     %s\n' "$@"; }

read -r -s -p "Mot de passe FTP de $FTP_UTILISATEUR : " LFTP_PASSWORD; echo
export LFTP_PASSWORD
# shellcheck source=scripts/lib-ftp.sh
source scripts/lib-ftp.sh
ftp_preparer

step "1/7 Vérifications locales"
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

step "2/7 Connexion FTP et état du serveur"
# Seuls des fichiers visibles sont testés : beaucoup de serveurs FTP masquent les fichiers « . » (.env).
REMOTE_LIST="$(ftp_run "cd '$FTP_DOSSIER'; cls -1 artisan; cls -1 database/migrations/" 2>/dev/null || true)"
FIRST_INSTALL="oui"; grep -qx 'artisan' <<<"$REMOTE_LIST" && FIRST_INSTALL="non"
echo "Première installation : $FIRST_INSTALL"

step "3/7 Paquet du commit $SHA"
scripts/preparer-paquet.sh "$REF"
NEW_MIGRATIONS="$(comm -23 <(ls build/paquet/database/migrations | sort) <(grep -o '[^/]*\.php$' <<<"$REMOTE_LIST" | sort) || true)"
if [[ -n "$NEW_MIGRATIONS" ]]; then echo "Nouvelles migrations :"; sed 's/^/  - /' <<<"$NEW_MIGRATIONS"; else echo "Aucune nouvelle migration."; fi
# Squelette de storage/ (dossiers, .gitignore, .htaccess) : envoyé SANS suppression,
# pour ne jamais toucher aux fichiers du serveur (envois, sauvegardes, journaux).
rm -rf build/squelette-storage && mkdir -p build/squelette-storage
(cd build/paquet/storage && find . -type d -exec mkdir -p ../../squelette-storage/{} \; \
  && find . \( -name '.gitignore' -o -name '.htaccess' \) -exec cp --parents {} ../../squelette-storage/ \;)

if [[ "$FIRST_INSTALL" == "non" ]]; then
  step "4/7 Mode maintenance"
  # Même fichier que « php artisan down » (pilote « file ») : le site répond 503 pendant l'envoi.
  printf '{"except":[],"redirect":null,"retry":60,"refresh":null,"secret":null,"status":503,"template":null}' > build/down
  ftp_run "cd '$FTP_DOSSIER'; put build/down -o storage/framework/down"
  echo "Site en maintenance."
  if [[ -n "$NEW_MIGRATIONS" ]]; then
    echo "Des migrations arrivent : sauvegarde chiffrée AVANT l'envoi."
    terminal "php artisan yewtod:backup"
    ask "La sauvegarde a-t-elle réussi (« Sauvegarde chiffrée créée ») ?" || { echo "Arrêt. Le site reste en maintenance : supprimez storage/framework/down par FTP pour le rouvrir."; exit 1; }
  fi
else
  step "4/7 Première installation : pas de maintenance ni de sauvegarde"
fi

step "5/7 Envoi des fichiers"
EXCLUDES=(--exclude-glob .env --exclude '^storage/' --exclude '^public/storage$' --exclude '^public/storage/'
          --exclude '^\.well-known/' --exclude '^cgi-bin/')
echo "Fichiers qui seraient SUPPRIMÉS sur le serveur (absents du commit) :"
ftp_run "cd '$FTP_DOSSIER'; mirror -R --dry-run --delete --no-perms --no-symlinks ${EXCLUDES[*]} build/paquet ." 2>/dev/null | grep -E '^rm ' | sed 's/^/  /' | head -50 || true
ask "Continuer l'envoi ?" || { echo "Arrêt. Supprimez storage/framework/down par FTP pour rouvrir le site."; exit 1; }
ftp_run "cd '$FTP_DOSSIER'; mirror -R --delete --no-perms --no-symlinks --parallel=6 ${EXCLUDES[*]} build/paquet .; mirror -R --no-perms --no-symlinks --parallel=6 build/squelette-storage storage"
# Le .env n'est envoyé qu'à la première installation ou sur demande (--env) : jamais écrasé par surprise.
if [[ "$SEND_ENV" == "oui" || "$FIRST_INSTALL" == "oui" ]]; then
  [[ -f "$ENV_LOCAL" ]] || { echo "Fichier $ENV_LOCAL introuvable (voir docs/DEPLOIEMENT.md, étape .env)."; exit 1; }
  grep -q '^APP_KEY=base64:' "$ENV_LOCAL" || { echo "APP_KEY absente de $ENV_LOCAL."; exit 1; }
  ftp_run "cd '$FTP_DOSSIER'; put '$ENV_LOCAL' -o .env; chmod 600 .env"
  echo ".env envoyé (droits 600)."
fi

step "6/7 Commandes à lancer dans le Terminal web"
CMDS=()
if [[ "$FIRST_INSTALL" == "oui" ]]; then
  CMDS+=("php -v                                   # version choisie dans le panneau (8.5)"
         "php artisan migrate --force --seed       # tables + rôles et réglages de référence"
         "php artisan storage:link"
         "php artisan yewtod:create-super-admin    # votre compte, interactif")
elif [[ -n "$NEW_MIGRATIONS" ]]; then
  CMDS+=("php artisan migrate --force")
fi
CMDS+=("php artisan optimize:clear && php artisan optimize"
       "php artisan yewtod:verifier-production"
       "php artisan up")
terminal "${CMDS[@]}"
ask "Ces commandes ont-elles été lancées sans erreur ?" || { echo "Corrigez puis relancez les commandes. Retour arrière possible : $0 <ancien commit>."; exit 1; }

step "7/7 Contrôle de santé"
code="$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL/up" || true)"
home="$(curl -s -o /dev/null -w '%{http_code}' "$SITE_URL/" || true)"
echo "/up → $code ; accueil → $home (401 normal si la protection de préproduction est active)"
[[ "$code" == "200" ]] || { echo "⚠ Le site ne répond pas correctement : vérifiez storage/logs puis envisagez $0 <ancien commit>."; exit 1; }
echo "Commit $SHA en ligne. Pensez à noter ce numéro (retour arrière : $0 $SHA~1)."
