"""Sondes manuelles (audit dynamique Yewtod). Cible : serveur en mode production."""
import re, statistics, time, sys, json
import urllib.request, urllib.parse, http.cookiejar

BASE = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8002"
results = []

def ok(name, passed, detail=""):
    results.append((name, passed, detail))
    print(("OK    " if passed else "ÉCHEC ") + name + (" — " + detail if detail else ""))

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k): return None

def client():
    jar = http.cookiejar.CookieJar()
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar), NoRedirect), jar

def req(opener, method, path, data=None, headers=None):
    body = urllib.parse.urlencode(data, doseq=True).encode() if isinstance(data, (dict, list)) else data
    r = urllib.request.Request(BASE + path, data=body, method=method, headers=headers or {})
    try:
        resp = opener.open(r, timeout=30)
        return resp.status, dict(resp.headers), resp.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as e:
        return e.code, dict(e.headers), e.read().decode("utf-8", "replace")

op, jar = client()
s, h, body = req(op, "GET", "/")
token = re.search(r'name="csrf-token" content="([^"]+)"', body).group(1)

# 1. CSRF : POST sans jeton refusé (419), avec jeton accepté.
s, _, _ = req(client()[0], "POST", "/newsletter", {"email": "csrf@exemple.fr"})
ok("CSRF : POST sans jeton refusé", s == 419, f"HTTP {s}")

# 2. Méthodes HTTP inattendues.
for m in ["PUT", "DELETE", "PATCH", "TRACE"]:
    s, _, _ = req(op, m, "/works")
    ok(f"Méthode {m} refusée sur /works", s in (405, 419, 400, 501), f"HTTP {s}")

# 3. Traversée de chemin (encodages variés) sur les routes de fichiers.
for p in ["/works/rapports/..%2f..%2f.env/telecharger", "/works/rapports/%2e%2e%2f%2e%2e%2f.env/telecharger",
          "/books/..%252f..%252f.env/livre-electronique", "/works/experimentations/x/fichiers/0%2f..%2f..%2f.env",
          "/storage/../.env", "/build/../../.env", "/.env", "/.git/config", "/composer.json", "/storage/logs/laravel.log"]:
    s, _, b = req(op, "GET", p)
    ok(f"Traversée/fichier sensible bloqué : {p}", s in (400, 403, 404) and "APP_KEY" not in b and "[core]" not in b, f"HTTP {s}")

# 4. Réflexion XSS : chaque paramètre GET reçoit une charge, jamais renvoyée telle quelle.
payload = '"><svg/onload=alert(1)><script>alert(2)</script>{{7*7}}'
for path, params in [("/works", ["q", "categorie", "axe", "tri", "du", "au", "serie", "auteur"]), ("/books", ["q", "categorie", "auteur", "annee", "tri"]),
                     ("/recherche", ["q"]), ("/une-page-inconnue", ["q"])]:
    for p in params:
        s, _, b = req(op, "GET", path + "?" + urllib.parse.urlencode({p: payload}))
        ok(f"XSS réfléchi : {path}?{p}", "<svg/onload" not in b and "<script>alert(2)" not in b and ">49<" not in b, f"HTTP {s}")

# 5. Énumération par le temps de réponse (connexion) : email existant vs inconnu.
def timing(email, n=12):
    times = []
    for _ in range(n):
        o, _ = client()
        _, _, b = req(o, "GET", "/login")
        t = re.search(r'name="_token" value="([^"]+)"', b).group(1)
        start = time.perf_counter()
        req(o, "POST", "/login", {"_token": t, "email": email, "password": "Mauvais-mot-de-passe-1!"})
        times.append(time.perf_counter() - start)
    return statistics.median(times)
known, unknown = timing("editeur@yewtod.test"), timing("personne-inconnue@yewtod.test")
ok("Énumération par le temps de connexion", abs(known - unknown) < 0.05, f"médiane connu {known*1000:.0f} ms, inconnu {unknown*1000:.0f} ms")

# 6. Upload temporaire Livewire sans authentification ni signature.
s, _, _ = req(op, "POST", "/livewire-44575472/upload-file", b"x", {"X-CSRF-TOKEN": token, "Content-Type": "multipart/form-data; boundary=x"})
ok("Upload Livewire non signé refusé", s in (401, 403, 419, 422, 404), f"HTTP {s}")
s, _, _ = req(op, "POST", "/livewire-44575472/update", json.dumps({"_token": token, "components": [{"snapshot": "{}", "updates": {}, "calls": []}]}).encode(),
              {"Content-Type": "application/json", "X-CSRF-TOKEN": token})
ok("Requête Livewire avec instantané forgé refusée", s >= 400, f"HTTP {s}")

# 7. Accès au dashboard et aux routes d'export sans session.
for p in ["/dashboard", "/dashboard/collaborations/export", "/dashboard/newsletter/export", "/dashboard/journal", "/apercu/1"]:
    s, h, _ = req(client()[0], "GET", p)
    ok(f"Accès anonyme refusé : {p}", s in (302, 401, 403) and ("login" in h.get("Location", "") or s != 302), f"HTTP {s} {h.get('Location','')}")

# 8. En-têtes de sécurité et cookies.
s, h, _ = req(client()[0], "GET", "/")
hl = {k.lower(): v for k, v in h.items()}
for name in ["content-security-policy", "x-frame-options", "x-content-type-options", "referrer-policy", "permissions-policy", "strict-transport-security"]:
    ok(f"En-tête présent : {name}", name in hl)
ok("Pas de X-Powered-By", "x-powered-by" not in hl, hl.get("x-powered-by", ""))
ok("Pas d'en-tête Server bavard", "php" not in hl.get("server", "").lower(), hl.get("server", ""))

# 9. Erreur serveur sans fuite (APP_DEBUG=false) : entrée très longue et types inattendus.
for p in ["/works?q=" + "a" * 5000, "/works?types[][]=x", "/books?annee[]=1", "/works/articles/" + "a" * 400]:
    s, _, b = req(op, "GET", p)
    ok(f"Pas de fuite d'erreur : {p[:40]}…", s < 500 and "Stack trace" not in b and "SQLSTATE" not in b and "vendor/laravel" not in b, f"HTTP {s}")

# 10. Redirection ouverte via les paramètres usuels.
for p in ["/login?redirect=https://attaquant.example", "/login?next=//attaquant.example", "/dashboard?url=https://attaquant.example", "//attaquant.example/", "/\\attaquant.example"]:
    s, h, _ = req(client()[0], "GET", p)
    loc = h.get("Location", "")
    ok(f"Pas de redirection ouverte : {p}", "attaquant.example" not in loc, f"HTTP {s} {loc}")

print(f"\n{sum(1 for r in results if r[1])}/{len(results)} sondes réussies.")
