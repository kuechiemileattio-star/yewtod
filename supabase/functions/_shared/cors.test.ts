// Tests unitaires de la logique CORS
// Exécution : deno test --allow-env supabase/functions/_shared/
import { isOriginAllowed, parseAllowedOrigins } from "./cors.ts";

// Assertion minimale (pas de dépendance externe)
function assertEquals(actual: unknown, expected: unknown) {
  const a = JSON.stringify(actual), e = JSON.stringify(expected);
  if (a !== e) throw new Error(`attendu ${e}, obtenu ${a}`);
}

const P = parseAllowedOrigins(" http://localhost:5173/ , https://yewtod.com,https://*.yewtod.pages.dev ");

Deno.test("parseAllowedOrigins nettoie espaces, / final et casse", () => {
  assertEquals(P, ["http://localhost:5173", "https://yewtod.com", "https://*.yewtod.pages.dev"]);
  assertEquals(parseAllowedOrigins(undefined), []);
  assertEquals(parseAllowedOrigins(" , "), []);
});

Deno.test("origines exactes", () => {
  assertEquals(isOriginAllowed("http://localhost:5173", P), true);
  assertEquals(isOriginAllowed("HTTPS://YEWTOD.COM", P), true);
  assertEquals(isOriginAllowed("http://localhost:5174", P), false);
  assertEquals(isOriginAllowed("http://yewtod.com", P), false); // http ≠ https
  assertEquals(isOriginAllowed("https://yewtod.com.evil.example", P), false);
});

Deno.test("joker = un seul sous-domaine", () => {
  assertEquals(isOriginAllowed("https://amelioration-etape-2.yewtod.pages.dev", P), true);
  assertEquals(isOriginAllowed("https://yewtod.pages.dev", P), false);
  assertEquals(isOriginAllowed("https://a.b.yewtod.pages.dev", P), false);
  assertEquals(isOriginAllowed("https://evil.example/x.yewtod.pages.dev", P), false);
  assertEquals(isOriginAllowed("https://x.yewtod.pages.dev.evil.example", P), false);
  assertEquals(isOriginAllowed("https://xyewtod.pages.dev", P), false); // le "." n'est pas un joker
});

Deno.test("sans origine ou sans liste : refus", () => {
  assertEquals(isOriginAllowed(null, P), false);
  assertEquals(isOriginAllowed("http://localhost:5173", []), false);
});
