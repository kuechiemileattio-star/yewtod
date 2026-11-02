// Tests des fonctions pures d'extract-pdf-ai
// Exécution : deno test supabase/functions/extract-pdf-ai/
import { looksLikePdf, normalizeExtraction, storagePathFromPublicUrl } from "./validation.ts";

function assertEquals(actual: unknown, expected: unknown) {
  const a = JSON.stringify(actual), e = JSON.stringify(expected);
  if (a !== e) throw new Error(`attendu ${e}, obtenu ${a}`);
}

const BASE = "https://htyimhfpggmofuqsdlpb.supabase.co";
const ok = (p: string) => `${BASE}/storage/v1/object/public/documents/${p}`;
const path = (u: unknown) => storagePathFromPublicUrl(u, BASE, "documents");

Deno.test("URL de notre bucket documents : acceptée", () => {
  assertEquals(path(ok("3f2a9c1e-1111-4222-8333-444455556666.pdf")), "3f2a9c1e-1111-4222-8333-444455556666.pdf");
  assertEquals(path(ok("dossier/rapport.PDF")), "dossier/rapport.PDF");
  assertEquals(path(ok("a.pdf") + "?download=1"), "a.pdf"); // paramètres ignorés
});

Deno.test("SSRF : autres hôtes refusés", () => {
  assertEquals(path("https://httpbin.org/status/418"), null);
  assertEquals(path("http://169.254.169.254/latest/meta-data/x.pdf"), null);
  assertEquals(path("https://htyimhfpggmofuqsdlpb.supabase.co.evil.example/storage/v1/object/public/documents/a.pdf"), null);
  assertEquals(path("https://evil.example@htyimhfpggmofuqsdlpb.supabase.co/storage/v1/object/public/documents/a.pdf"), null); // identifiants dans l URL : refus
  assertEquals(path("https://user:pw@htyimhfpggmofuqsdlpb.supabase.co/storage/v1/object/public/documents/a.pdf"), null);
  assertEquals(path(`http://htyimhfpggmofuqsdlpb.supabase.co/storage/v1/object/public/documents/a.pdf`), null); // http ≠ https
});

Deno.test("autres buckets et chemins piégés refusés", () => {
  assertEquals(path(`${BASE}/storage/v1/object/public/collaboration-attachments/a.pdf`), null);
  assertEquals(path(`${BASE}/storage/v1/object/sign/documents/a.pdf`), null);
  assertEquals(path(ok("../avatars/a.pdf")), null);
  assertEquals(path(ok("%2e%2e/avatars/a.pdf")), null);
  assertEquals(path(ok("a%2F..%2Fb.pdf")), null);
  assertEquals(path(ok("a//b.pdf")), null);
  assertEquals(path(ok("image.png")), null);
  assertEquals(path(ok("a b.pdf")), null);
  assertEquals(path(ok("%E0%A4%A.pdf")), null); // encodage invalide
  assertEquals(path(ok("x".repeat(600) + ".pdf")), null);
  assertEquals(path(42), null);
  assertEquals(path("pas une url"), null);
});

Deno.test("signature PDF", () => {
  assertEquals(looksLikePdf(new TextEncoder().encode("%PDF-1.7\n...")), true);
  assertEquals(looksLikePdf(new TextEncoder().encode("<html>")), false);
  assertEquals(looksLikePdf(new Uint8Array([0x25, 0x50])), false);
});

Deno.test("sortie Gemini normalisée", () => {
  const n = normalizeExtraction({
    title: "  Titre  ", authors: ["A", 3, "", "B"], summary: "x".repeat(6000),
    language: "fr", doi: "10.1000/xyz123", pageCount: "12",
    tableOfContents: [{ title: "Intro", page: 1, summary: "s" }, { title: "" }, "bad", { title: "Fin", page: -4 }],
    pirate: "<script>",
  });
  assertEquals(n.title, "Titre");
  assertEquals(n.authors, ["A", "B"]);
  assertEquals(n.summary!.length, 5000);
  assertEquals(n.pageCount, 12);
  assertEquals(n.doi, "10.1000/xyz123");
  assertEquals(n.tableOfContents, [{ title: "Intro", page: 1, summary: "s" }, { title: "Fin", page: null, summary: "" }]);
  assertEquals("pirate" in n, false);
  assertEquals(normalizeExtraction(null).tableOfContents, []);
  assertEquals(normalizeExtraction({ language: "français", doi: "javascript:alert(1)" }).language, null);
  assertEquals(normalizeExtraction({ doi: "javascript:alert(1)" }).doi, null);
});
