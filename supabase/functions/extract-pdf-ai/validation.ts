// Fonctions PURES d'extract-pdf-ai (aucun accès réseau) → testables isolément.
// Tests : deno test supabase/functions/extract-pdf-ai/

/**
 * Anti-SSRF : on n'accepte PAS une URL arbitraire à télécharger. On accepte
 * uniquement l'URL publique d'un PDF de NOTRE Storage :
 *   <supabaseUrl>/storage/v1/object/public/<bucket>/<chemin>.pdf
 * et on renvoie <chemin>. La fonction télécharge ensuite elle-même ce chemin
 * dans notre Storage : l'hôte contacté n'est plus choisi par le client.
 * Renvoie null si l'URL ne correspond pas exactement.
 */
export function storagePathFromPublicUrl(raw: unknown, supabaseUrl: string, bucket: string): string | null {
  if (typeof raw !== "string" || raw.length > 2048) return null;
  let url: URL, base: URL;
  try {
    url = new URL(raw);
    base = new URL(supabaseUrl);
  } catch {
    return null;
  }
  if (url.origin !== base.origin) return null;
  if (url.username || url.password) return null;

  const prefix = `/storage/v1/object/public/${bucket}/`;
  if (!url.pathname.startsWith(prefix)) return null;

  let path: string;
  try {
    path = decodeURIComponent(url.pathname.slice(prefix.length));
  } catch {
    return null;
  }
  // Chemins produits par useStorageUpload : <uuid>.pdf, éventuellement sous un dossier.
  if (path.length > 512) return null;
  if (path.split("/").some(seg => seg === "" || seg === "." || seg === "..")) return null;
  if (!/^[A-Za-z0-9_\-./]+$/.test(path)) return null;
  if (!/\.pdf$/i.test(path)) return null;
  return path;
}

/** Un vrai PDF commence par la signature "%PDF-" (octets 25 50 44 46 2D). */
export function looksLikePdf(bytes: Uint8Array): boolean {
  const sig = [0x25, 0x50, 0x44, 0x46, 0x2d];
  return bytes.length >= sig.length && sig.every((b, i) => bytes[i] === b);
}

// ---------------------------------------------------------------------------
// La réponse de Gemini est une DONNÉE NON FIABLE (le PDF peut contenir du
// texte écrit pour manipuler le modèle) : on n'en garde que les champs
// attendus, avec le bon type et une taille bornée.
// ---------------------------------------------------------------------------

export interface Extraction {
  title: string | null;
  authors: string[];
  summary: string | null;
  language: string | null;
  doi: string | null;
  pageCount: number | null;
  tableOfContents: { title: string; page: number | null; summary: string }[];
}

function str(v: unknown, max: number): string | null {
  if (typeof v !== "string") return null;
  const s = v.trim();
  return s ? s.slice(0, max) : null;
}

function int(v: unknown, min: number, max: number): number | null {
  const n = typeof v === "string" ? Number(v) : v;
  return typeof n === "number" && Number.isInteger(n) && n >= min && n <= max ? n : null;
}

export function normalizeExtraction(raw: unknown): Extraction {
  const o = raw && typeof raw === "object" && !Array.isArray(raw) ? raw as Record<string, unknown> : {};
  const language = str(o.language, 8);
  const doi = str(o.doi, 200);
  return {
    title: str(o.title, 500),
    authors: Array.isArray(o.authors)
      ? o.authors.map(a => str(a, 200)).filter((a): a is string => a !== null).slice(0, 50)
      : [],
    summary: str(o.summary, 5000),
    language: language && /^[a-z]{2,3}(-[A-Za-z]{2,4})?$/.test(language) ? language : null,
    doi: doi && /^10\.\d{4,9}\/\S+$/.test(doi) ? doi : null,
    pageCount: int(o.pageCount, 1, 100000),
    tableOfContents: Array.isArray(o.tableOfContents)
      ? o.tableOfContents
        .filter((s): s is Record<string, unknown> => !!s && typeof s === "object" && !Array.isArray(s))
        .map(s => ({ title: str(s.title, 500) ?? "", page: int(s.page, 1, 100000), summary: str(s.summary, 2000) ?? "" }))
        .filter(s => s.title !== "")
        .slice(0, 200)
      : [],
  };
}
