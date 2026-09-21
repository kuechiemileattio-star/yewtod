// Edge Function: extract-pdf-ai
//
// Sends a PDF (already uploaded to Storage) to the Claude API and asks it to
// return structured metadata — title, authors, summary, table of contents,
// DOI, language — as real reading comprehension of the document rather than
// the client-side pdfjs extraction (lib/pdfMetadata.js), which only reads
// embedded bookmarks/raw text positions and has no understanding of meaning.
//
// The PDF never reaches the browser bundle for this — the API key must stay
// server-side, which is exactly why this has to be an Edge Function and not
// a direct fetch() from the dashboard.
//
// Requires the ANTHROPIC_API_KEY secret:
//   supabase secrets set ANTHROPIC_API_KEY=sk-ant-... --project-ref <project-ref>
//
// Deploy:
//   supabase functions deploy extract-pdf-ai --project-ref <project-ref>

import { createClient } from "npm:@supabase/supabase-js@2";

const SUPABASE_URL = Deno.env.get("SUPABASE_URL");
const ANON_KEY = Deno.env.get("SUPABASE_ANON_KEY");
const ANTHROPIC_API_KEY = Deno.env.get("ANTHROPIC_API_KEY");

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type",
};

const EXTRACTION_PROMPT = `Tu vas lire un document PDF (rapport, article ou livre) et en extraire des métadonnées structurées.

Réponds UNIQUEMENT avec un objet JSON valide (rien avant, rien après), avec exactement ces clés :
{
  "title": "titre du document tel qu'il apparaît, ou null si absent",
  "authors": ["auteur 1", "auteur 2"],
  "summary": "un résumé fidèle du document en 2-4 phrases, en français",
  "language": "code de langue à deux lettres du contenu du document, ex: fr, en",
  "doi": "le DOI s'il est mentionné explicitement dans le document, sinon null",
  "pageCount": nombre de pages du document (entier),
  "tableOfContents": [
    { "title": "titre de la section ou du chapitre", "page": numéro de page où elle commence (entier), "summary": "1-2 phrases résumant CE QUI EST DIT dans cette section précise, en français" }
  ]
}

Consignes :
- "tableOfContents" doit refléter la vraie structure du document (introduction, sections principales, conclusion...), pas une liste générique.
- Chaque résumé de section doit être spécifique à son contenu réel, pas une paraphrase du titre.
- Si le document n'a pas de titre de page de garde, déduis-le du contenu.
- N'invente aucune information : si une donnée n'est pas présente dans le document, utilise null (ou un tableau vide).`;

Deno.serve(async req => {
  if (req.method === "OPTIONS") return new Response("ok", { headers: corsHeaders });

  try {
    if (!ANTHROPIC_API_KEY) throw new Error("ANTHROPIC_API_KEY n'est pas configurée sur ce projet Supabase.");

    const authHeader = req.headers.get("Authorization");
    if (!authHeader) throw new Error("Missing authorization header");

    // Just confirms the caller is a logged-in admin — no service_role needed,
    // this function never touches the database itself.
    const callerClient = createClient(SUPABASE_URL, ANON_KEY, {
      global: { headers: { Authorization: authHeader } },
    });
    const { data: { user: caller }, error: callerError } = await callerClient.auth.getUser();
    if (callerError || !caller) throw new Error("Invalid session");

    const { pdfUrl } = await req.json();
    if (!pdfUrl) throw new Error("pdfUrl est requis");

    // Fetch the PDF ourselves and send it as base64 — Anthropic's PDF
    // support also accepts a direct {type:"url"} source, but fetching it
    // here works even for buckets/links Anthropic's own fetcher can't reach.
    const pdfRes = await fetch(pdfUrl);
    if (!pdfRes.ok) throw new Error("Impossible de télécharger le PDF depuis Supabase Storage.");
    const pdfBuffer = await pdfRes.arrayBuffer();
    const pdfSizeMb = pdfBuffer.byteLength / (1024 * 1024);
    if (pdfSizeMb > 32) throw new Error(`Ce PDF fait ${pdfSizeMb.toFixed(1)} Mo — la limite de l'API Claude pour un document est de 32 Mo.`);
    // Convert to base64 in chunks — spreading the whole byte array into
    // String.fromCharCode(...) at once blows the JS call-stack argument
    // limit (~65k) for any PDF above a couple hundred KB.
    const bytes = new Uint8Array(pdfBuffer);
    let binary = "";
    const chunkSize = 8192;
    for (let i = 0; i < bytes.length; i += chunkSize) {
      binary += String.fromCharCode(...bytes.subarray(i, i + chunkSize));
    }
    const base64Pdf = btoa(binary);

    const anthropicRes = await fetch("https://api.anthropic.com/v1/messages", {
      method: "POST",
      headers: {
        "x-api-key": ANTHROPIC_API_KEY,
        "anthropic-version": "2023-06-01",
        "content-type": "application/json",
      },
      body: JSON.stringify({
        model: "claude-sonnet-5",
        max_tokens: 4096,
        messages: [{
          role: "user",
          content: [
            { type: "document", source: { type: "base64", media_type: "application/pdf", data: base64Pdf } },
            { type: "text", text: EXTRACTION_PROMPT },
          ],
        }],
      }),
    });

    if (!anthropicRes.ok) {
      const errBody = await anthropicRes.text();
      throw new Error(`Erreur de l'API Claude (${anthropicRes.status}) : ${errBody.slice(0, 300)}`);
    }

    const anthropicJson = await anthropicRes.json();
    const rawText = anthropicJson.content?.find((b: { type: string }) => b.type === "text")?.text || "";
    // Claude occasionally wraps JSON in a ```json fence despite instructions — strip it if present.
    const cleaned = rawText.trim().replace(/^```json\s*/i, "").replace(/```$/i, "").trim();

    let extracted;
    try {
      extracted = JSON.parse(cleaned);
    } catch {
      throw new Error("La réponse de Claude n'était pas un JSON valide — réessaie, ou le document est peut-être illisible (scan de mauvaise qualité, etc.).");
    }

    return new Response(JSON.stringify(extracted), {
      headers: { ...corsHeaders, "Content-Type": "application/json" },
    });
  } catch (err) {
    console.error("extract-pdf-ai error:", err.message);
    return new Response(JSON.stringify({ error: err.message }), {
      status: 400,
      headers: { ...corsHeaders, "Content-Type": "application/json" },
    });
  }
});
