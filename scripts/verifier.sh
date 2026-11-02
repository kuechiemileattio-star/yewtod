#!/usr/bin/env bash
# ============================================================================
# scripts/verifier.sh — contrôle de cohérence du dépôt Yewtod SS
#
# Usage :  bash scripts/verifier.sh            (complet : npm ci inclus)
#          bash scripts/verifier.sh --rapide   (sans réinstaller node_modules)
#   ou     npm run verify
#
# Ne modifie RIEN en ligne : lecture seule côté Supabase.
# Chaque contrôle affiche ✅ ou ❌ ; le script continue pour donner un bilan
# complet, puis sort avec le code 1 si au moins un contrôle a échoué.
# ============================================================================
set -euo pipefail

DEV_REF="htyimhfpggmofuqsdlpb"   # yewtod-dev — le SEUL projet autorisé ici
MIN_NODE_MAJOR=20
RAPIDE=0
[[ "${1:-}" == "--rapide" ]] && RAPIDE=1

ECHECS=0
ok()   { printf '  \033[32m✅ %s\033[0m\n' "$1"; }
ko()   { printf '  \033[31m❌ %s\033[0m\n' "$1"; ECHECS=$((ECHECS + 1)); }
info() { printf '     %s\n' "$1"; }
titre(){ printf '\n\033[1m▶ %s\033[0m\n' "$1"; }

# --- 1. Emplacement -----------------------------------------------------------
titre "1. Racine du dépôt"
if [[ -f package.json && -d supabase/migrations ]]; then
  ok "lancé depuis la racine ($(pwd))"
else
  ko "à lancer depuis la racine du repo (package.json + supabase/migrations introuvables)"
  exit 1   # tout le reste dépend de ça
fi

# --- 2. Projet Supabase lié ---------------------------------------------------
titre "2. Projet Supabase lié"
REF_FILE="supabase/.temp/project-ref"
if [[ ! -f "$REF_FILE" ]]; then
  ko "aucun projet lié ($REF_FILE absent) → npx supabase link --project-ref $DEV_REF"
  exit 1
fi
REF="$(tr -d '[:space:]' < "$REF_FILE")"
if [[ "$REF" == "$DEV_REF" ]]; then
  ok "lié à yewtod-dev ($REF)"
else
  ko "lié à '$REF' au lieu de yewtod-dev ($DEV_REF) — ARRÊT, rien n'est lancé contre ce projet"
  exit 1
fi

# --- 3. Node ------------------------------------------------------------------
titre "3. Version de Node"
NODE_MAJOR="$(node -p 'process.versions.node.split(".")[0]' 2>/dev/null || echo 0)"
if (( NODE_MAJOR >= MIN_NODE_MAJOR )); then
  ok "Node $(node -v)"
else
  ko "Node >= $MIN_NODE_MAJOR requis (trouvé : $(node -v 2>/dev/null || echo 'absent')) → nvm use 20"
fi

# --- 4. Fichiers sensibles suivis par git --------------------------------------
titre "4. Aucun fichier local/secret suivi par git"
SENSIBLES="$(git ls-files | grep -E '(^|/)\.env($|\.)|^supabase/\.temp/|^supabase/\.branches/|^supabase/functions/(.*/)?\.env$' | grep -v '^\.env\.example$' || true)"
if [[ -z "$SENSIBLES" ]]; then
  ok "aucun .env / supabase/.temp suivi"
else
  ko "fichiers qui ne devraient pas être dans git :"
  while IFS= read -r f; do info "$f"; done <<< "$SENSIBLES"
  info "→ git rm --cached <fichier> (le fichier reste sur ton disque)"
fi

# --- 5. Dépendances -----------------------------------------------------------
titre "5. Dépendances npm"
if (( RAPIDE )); then
  info "(--rapide : npm ci ignoré)"
elif npm ci --no-audit --no-fund >/tmp/yewtod-npm-ci.log 2>&1; then
  ok "npm ci (package-lock.json cohérent avec package.json)"
else
  ko "npm ci a échoué — voir /tmp/yewtod-npm-ci.log"
fi

if npm audit --audit-level=high >/tmp/yewtod-audit.log 2>&1; then
  ok "npm audit : $(tail -n 1 /tmp/yewtod-audit.log)"
else
  ko "npm audit : vulnérabilité(s) high/critical — voir /tmp/yewtod-audit.log"
  grep -E 'vulnerabilit' /tmp/yewtod-audit.log | tail -n 1 | sed 's/^/     /'
fi

# --- 6. Build -----------------------------------------------------------------
titre "6. Build de production"
if npm run build >/tmp/yewtod-build.log 2>&1; then
  ok "npm run build ($(grep -oE 'built in [0-9.]+m?s' /tmp/yewtod-build.log || echo 'OK'))"
else
  ko "npm run build a échoué — voir /tmp/yewtod-build.log"
  tail -n 15 /tmp/yewtod-build.log | sed 's/^/     /'
fi

# --- 7. Migrations local ↔ en ligne --------------------------------------------
titre "7. Migrations : dossier local ↔ yewtod-dev"
info "(la CLI peut demander le mot de passe de la base yewtod-dev)"
# stdout seulement : une éventuelle invite de mot de passe reste visible.
if LISTE="$(npx supabase migration list --linked)"; then
  # Lignes du tableau "Local | Remote | Time" (hors en-tête et séparateur)
  LIGNES="$(printf '%s\n' "$LISTE" | grep '|' | grep -v -E 'Local|---' || true)"
  if [[ -z "$LIGNES" ]]; then
    ko "format de sortie non reconnu — sortie brute :"
    printf '%s\n' "$LISTE" | sed 's/^/     /'
  else
    TOTAL=0; DECALAGES=""
    while IFS='|' read -r loc rem _; do
      loc="$(echo "$loc" | xargs)"; rem="$(echo "$rem" | xargs)"
      TOTAL=$((TOTAL + 1))
      if [[ "$loc" != "$rem" ]]; then
        if [[ -z "$rem" ]]; then DECALAGES+="local seulement (pas encore poussée) : $loc"$'\n'
        elif [[ -z "$loc" ]]; then DECALAGES+="en ligne seulement (fichier absent ici !) : $rem"$'\n'
        else DECALAGES+="désaccord : local=$loc / en ligne=$rem"$'\n'; fi
      fi
    done <<< "$LIGNES"
    if [[ -z "$DECALAGES" ]]; then
      ok "$TOTAL migrations, toutes appliquées et présentes localement"
    else
      ko "décalage(s) entre le dossier et la base :"
      printf '%s' "$DECALAGES" | sed 's/^/     /'
    fi
  fi
else
  ko "npx supabase migration list --linked a échoué (réseau ? mot de passe ? npx supabase login ?)"
fi

# --- Bilan --------------------------------------------------------------------
echo
if (( ECHECS == 0 )); then
  printf '\033[1;32m✔ Tout est cohérent.\033[0m\n'
else
  printf '\033[1;31m✘ %d contrôle(s) en échec.\033[0m\n' "$ECHECS"
  exit 1
fi
