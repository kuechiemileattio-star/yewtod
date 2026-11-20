#!/usr/bin/env bash
# =====================================================================
# Prépare le paquet à envoyer sur l'hébergement (décision D73).
#
#   scripts/preparer-paquet.sh [commit]      # par défaut : HEAD
#
# Le paquet est construit à partir d'un COMMIT (git archive), jamais des
# fichiers en cours de modification : ce qui part en ligne est exactement
# ce qui a été testé et versionné. Résultat : build/paquet/ contenant
#   - le code du commit, sans tests, docs, scripts ni fichiers de dev ;
#   - vendor/ installé sans les outils de développement (PHP 8.3) ;
#   - public/build/ compilé (aucun Node sur le serveur) ;
#   - un .htaccess « Require all denied » dans chaque dossier de premier
#     niveau autre que public/ (3e couche de protection, voir .htaccess) ;
#   - VERSION (commit et date), pour savoir ce qui tourne en ligne.
# =====================================================================
set -euo pipefail
cd "$(dirname "$0")/.."

REF="${1:-HEAD}"
SHA="$(git rev-parse --short "$REF")"
OUT="build/paquet"
step() { printf '\n\033[1;34m▶ %s\033[0m\n' "$*"; }

step "Extraction du commit $SHA"
rm -rf "$OUT" && mkdir -p "$OUT"
git archive "$REF" | tar -x -C "$OUT"

step "Dépendances PHP sans outils de développement"
if [[ -f composer.lock ]]; then
  cp composer.lock "$OUT/"
else
  echo "⚠ composer.lock absent : les versions seront résolues maintenant (générez et committez composer.lock)."
fi
(cd "$OUT" && composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --prefer-dist --no-progress)
# Si Composer a dû cloner un paquet (téléchargement impossible), on retire ses métadonnées Git.
find "$OUT/vendor" -name .git -type d -prune -exec rm -rf {} +

step "Compilation des assets"
(cd "$OUT" && npm ci --no-audit --no-fund --silent && npm run build --silent && rm -rf node_modules)

step "Nettoyage (rien d'inutile ni de sensible en ligne)"
(cd "$OUT" && rm -rf tests docs scripts .github .editorconfig .gitattributes .gitignore phpunit.xml phpstan.neon pint.json \
  package.json package-lock.json vite.config.js README.md .npmrc database/sql public/hot)
# Les fichiers .env ne viennent jamais du dépôt ; on vérifie quand même.
find "$OUT" -maxdepth 1 -name '.env*' ! -name '.env.example' -exec rm -f {} +
rm -f "$OUT/.env.example"

step "Protection des dossiers hors de public/"
DENY='# Généré par preparer-paquet.sh : jamais servi par le web (D73).\nRequire all denied\n'
for dir in "$OUT"/*/; do
  name="$(basename "$dir")"
  [[ "$name" == "public" ]] && continue
  if [[ "$name" == "storage" ]]; then
    # Pas de refus sur storage/ lui-même : l'URL /storage/… des fichiers publics passe par
    # ce chemin avant d'être réécrite vers public/storage (lien vers storage/app/public).
    # On protège donc ses sous-dossiers ; storage/app/public garde son propre .htaccess.
    for sub in app framework logs; do printf "$DENY" > "$dir/$sub/.htaccess"; done
    continue
  fi
  printf "$DENY" > "$dir/.htaccess"
done

printf 'commit=%s\ndate=%s\n' "$(git rev-parse "$REF")" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "$OUT/VERSION"

step "Contrôles du paquet"
[[ -f "$OUT/public/build/manifest.json" ]] || { echo "Assets non compilés."; exit 1; }
[[ -f "$OUT/vendor/autoload.php" ]] || { echo "vendor/ absent."; exit 1; }
[[ -f "$OUT/.htaccess" ]] || { echo ".htaccess racine absent."; exit 1; }
if [[ -d "$OUT/vendor/pestphp" || -d "$OUT/vendor/larastan" ]]; then echo "Outils de développement présents dans vendor/."; exit 1; fi
echo "Paquet prêt : $OUT ($(du -sh "$OUT" | cut -f1), $(find "$OUT" -type f | wc -l) fichiers, commit $SHA)."
