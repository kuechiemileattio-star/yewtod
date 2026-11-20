# shellcheck shell=bash
# Connexion FTP commune à deployer-ftp.sh et recuperer-sauvegardes-ftp.sh (D73, D75).
#
# Trois modes, du plus sûr au moins sûr :
#   1. FTP_TLS="oui", FTP_EMPREINTE vide : FTPS avec vérification complète
#      (le nom FTP_HOTE doit figurer dans le certificat).
#   2. FTP_TLS="oui", FTP_EMPREINTE renseignée : FTPS ; le certificat doit
#      être signé par une autorité reconnue ET avoir exactement cette empreinte
#      SHA-256 (vérifiée avant chaque connexion) ; seul le contrôle du nom est
#      sauté. Cas de LWS : ftp.yewtod.com présente le certificat *.lwspanel.com.
#   3. FTP_TLS="non" : FTP en clair (déconseillé : mot de passe lisible).

# Empreinte SHA-256 du certificat présenté par le serveur FTP (format AA:BB:…).
ftp_empreinte_serveur() {
  openssl s_client -connect "$FTP_HOTE:21" -starttls ftp -servername "$FTP_HOTE" </dev/null 2>/dev/null \
    | openssl x509 -noout -fingerprint -sha256 2>/dev/null | cut -d= -f2
}

# Compare deux empreintes sans tenir compte de la casse ni des « : ».
ftp_empreintes_egales() {
  local a b
  a="$(tr -d ':[:space:]' <<<"$1" | tr '[:lower:]' '[:upper:]')"
  b="$(tr -d ':[:space:]' <<<"$2" | tr '[:lower:]' '[:upper:]')"
  [[ -n "$a" && "$a" == "$b" ]]
}

# Prépare SSL_OPTS selon le mode ; s'arrête si l'empreinte ne correspond pas.
ftp_preparer() {
  if [[ "${FTP_TLS:-oui}" != "oui" ]]; then
    echo "⚠ FTP NON chiffré : le mot de passe et les fichiers circulent en clair."
    SSL_OPTS="set ftp:ssl-allow false;"
    return
  fi
  SSL_OPTS="set ftp:ssl-force true; set ftp:ssl-protect-data true; set ssl:verify-certificate true;"
  if [[ -n "${FTP_EMPREINTE:-}" ]]; then
    local vue
    vue="$(ftp_empreinte_serveur)"
    if ! ftp_empreintes_egales "$vue" "$FTP_EMPREINTE"; then
      echo "ARRÊT : le certificat du serveur FTP n'est pas celui attendu."
      echo "  attendu : $FTP_EMPREINTE"
      echo "  reçu    : ${vue:-aucun (serveur injoignable ?)}"
      echo "Si LWS a renouvelé son certificat, vérifiez-le (docs/DEPLOIEMENT.md § 3) avant de mettre à jour FTP_EMPREINTE."
      exit 1
    fi
    echo "Certificat FTP reconnu (empreinte vérifiée)."
    SSL_OPTS+=" set ssl:check-hostname no;"
  fi
}

ftp_run() { lftp -e "set net:max-retries 2; set net:timeout 30; $SSL_OPTS $1; bye" --env-password -u "$FTP_UTILISATEUR" "$FTP_HOTE"; }
