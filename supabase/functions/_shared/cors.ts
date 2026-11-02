// supabase/functions/_shared/cors.ts
//
// CORS commun aux Edge Functions (dossier `_shared` : jamais déployé seul,
// inclus par chaque fonction qui l'importe).
//
// Rappel : CORS n'est PAS un contrôle d'accès — curl ou un serveur l'ignorent.
// C'est une règle du NAVIGATEUR : elle décide quels sites web peuvent lire la
// réponse d'une requête faite depuis leur page. L'accès réel reste protégé par
// le JWT + les permissions vérifiées dans chaque fonction.
//
// Origines autorisées : secret ALLOWED_ORIGINS, liste séparée par des virgules.
//   npx supabase secrets set ALLOWED_ORIGINS="http://localhost:5173"
//   (plus tard : "https://yewtod.com,https://*.yewtod.pages.dev")
// Un motif "https://*.domaine" accepte UN seul sous-domaine (lettres, chiffres,
// tirets) : https://ma-branche.yewtod.pages.dev oui, https://a.b.yewtod.pages.dev non.
// Secret absent ou vide → AUCUNE origine autorisée (fail closed).

const ALLOWED_HEADERS = "authorization, x-client-info, apikey, content-type";

/** Transforme "a, b ,c" en motifs d'origine nettoyés (sans / final). */
export function parseAllowedOrigins(raw: string | undefined | null): string[] {
  return (raw ?? "")
    .split(",")
    .map(s => s.trim().replace(/\/+$/, "").toLowerCase())
    .filter(Boolean);
}

function escapeRegExp(s: string): string {
  return s.replace(/[.+?^${}()|[\]\\]/g, "\\$&");
}

/** L'origine (ex. "http://localhost:5173") correspond-elle à l'un des motifs ? */
export function isOriginAllowed(origin: string | null, patterns: string[]): boolean {
  if (!origin) return false;
  const o = origin.trim().toLowerCase();
  return patterns.some(p => {
    if (!p.includes("*")) return o === p;
    // "*" = exactement un label DNS : [a-z0-9-]+
    const re = new RegExp("^" + p.split("*").map(escapeRegExp).join("[a-z0-9-]+") + "$");
    return re.test(o);
  });
}

const PATTERNS = parseAllowedOrigins(Deno.env.get("ALLOWED_ORIGINS"));
if (PATTERNS.length === 0) {
  console.warn("ALLOWED_ORIGINS n'est pas configuré : toutes les requêtes navigateur cross-origin seront refusées.");
}

/** En-têtes CORS à ajouter à CHAQUE réponse (vide si l'origine n'est pas autorisée). */
export function corsHeaders(req: Request): Record<string, string> {
  const origin = req.headers.get("Origin");
  const headers: Record<string, string> = { Vary: "Origin" };
  if (isOriginAllowed(origin, PATTERNS)) {
    headers["Access-Control-Allow-Origin"] = origin!;
    headers["Access-Control-Allow-Headers"] = ALLOWED_HEADERS;
    headers["Access-Control-Allow-Methods"] = "POST, OPTIONS";
    headers["Access-Control-Max-Age"] = "600";
  }
  return headers;
}

/** Réponse au preflight OPTIONS : 204 si origine autorisée, 403 sinon. null si ce n'est pas un preflight. */
export function handlePreflight(req: Request): Response | null {
  if (req.method !== "OPTIONS") return null;
  const headers = corsHeaders(req);
  const allowed = "Access-Control-Allow-Origin" in headers;
  return new Response(null, { status: allowed ? 204 : 403, headers });
}

/** Réponse JSON avec les en-têtes CORS de la requête. */
export function jsonResponse(req: Request, body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { ...corsHeaders(req), "Content-Type": "application/json" },
  });
}
