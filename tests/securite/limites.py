import re, urllib.request, urllib.parse, http.cookiejar
BASE = "http://127.0.0.1:8001"
def session():
    jar = http.cookiejar.CookieJar()
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
def status(op, method, path, data=None):
    r = urllib.request.Request(BASE + path, data=urllib.parse.urlencode(data).encode() if data else None, method=method)
    try:
        return op.open(r, timeout=30).status
    except urllib.error.HTTPError as e:
        return e.code
def token(op, path):
    return re.search(r'name="_token" value="([^"]+)"|name="csrf-token" content="([^"]+)"', op.open(BASE + path).read().decode()).group(1, 2)
def first_429(fn, n):
    for i in range(1, n + 1):
        if fn() == 429:
            return i
    return None
op = session(); t = [x for x in token(op, "/login") if x][0]
print("Connexion (même email + IP)   : 429 à la tentative", first_429(lambda: status(op, "POST", "/login", {"_token": t, "email": "editeur@yewtod.test", "password": "Faux-mdp-1234!"}), 12))
op = session(); t = [x for x in token(op, "/meet") if x][0]
print("Newsletter (par IP)           : 429 à l'envoi", first_429(lambda: status(op, "POST", "/newsletter", {"_token": t, "email": "x@exemple.fr"}), 10))
op = session()
print("Téléchargements (par IP)      : 429 à la requête", first_429(lambda: status(op, "GET", "/works/rapports/rapport-2026-sur-la-precarite-etudiante/telecharger"), 40))
op = session()
print("Recherche globale (par IP)    : 429 à la requête", first_429(lambda: status(op, "GET", "/recherche?q=rapport"), 70))
op = session(); t = [x for x in token(op, "/mot-de-passe-oublie") if x][0]
print("Mot de passe oublié (par IP)  : 429 à la demande", first_429(lambda: status(op, "POST", "/mot-de-passe-oublie", {"_token": t, "email": "editeur@yewtod.test"}), 12))
