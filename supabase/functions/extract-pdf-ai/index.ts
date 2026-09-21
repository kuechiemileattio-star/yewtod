// Edge Function: extract-pdf-ai
//
// Sends a PDF (already uploaded to Storage) to Google Gemini and asks it to
// return structured metadata — title, authors, summary, table of contents,
// DOI, language — as real reading comprehension of the document rather than
// the client-side pdfjs extraction (lib/pdfMetadata.js), which only reads
// embedded bookmarks/raw text positions and has no understanding of meaning.
//
// Uses Gemini instead of Claude specifically because Gemini has a free tier
// (no credit card, no billing) generous enough for personal/occasional use —
// Claude requires a paid API balance.
//
// The PDF never reaches the browser bundle for this — the API key must stay
// server-side, which is exactly why this has to be an Edge Function and not
// a direct fetch() from the dashboard.
//
// Requires the GEMINI_API_KEY secret (free key, no card needed, from
// aistudio.google.com/app/apikey):
//   supabase secrets set GEMINI_API_KEY=... --project-ref <project-ref>
//
// Deploy:
//   supabase functions deploy extract-pdf-ai --project-ref <project-ref>

import { createClient } from "npm:@supabase/supabase-js@2";

const SUPABASE_URL = Deno.env.get("SUPABASE_URL");
const ANON_KEY = Deno.env.get("SUPABASE_ANON_KEY");
const GEMINI_API_KEY = Deno.env.get("GEMINI_API_KEY");
const GEMINI_MODEL = "gemini-2.0-flash";

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
    if (!GEMINI_API_KEY) throw new Error("GEMINI_API_KEY n'est pas configurée sur ce projet Supabase.");

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

    // Fetch the PDF ourselves and send it as base64.
    const pdfRes = await fetch(pdfUrl);
    if (!pdfRes.ok) throw new Error("Impossible de télécharger le PDF depuis Supabase Storage.");
    const pdfBuffer = await pdfRes.arrayBuffer();
    const pdfSizeMb = pdfBuffer.byteLength / (1024 * 1024);
    if (pdfSizeMb > 20) throw new Error(`Ce PDF fait ${pdfSizeMb.toFixed(1)} Mo — la limite de l'API Gemini pour un document inline est de 20 Mo.`);
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

    const geminiRes = await fetch(
      `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent?key=${GEMINI_API_KEY}`,
      {
        method: "POST",
        headers: { "content-type": "application/json" },
        body: JSON.stringify({
          contents: [{
            parts: [
              { inline_data: { mime_type: "application/pdf", data: base64Pdf } },
              { text: EXTRACTION_PROMPT },
            ],
          }],
          generationConfig: { responseMimeType: "application/json" },
        }),
      }
    );

    if (!geminiRes.ok) {
      const errBody = await geminiRes.text();
      throw new Error(`Erreur de l'API Gemini (${geminiRes.status}) : ${errBody.slice(0, 300)}`);
    }

    const geminiJson = await geminiRes.json();
    const rawText = geminiJson.candidates?.[0]?.content?.parts?.[0]?.text || "";
    const cleaned = rawText.trim().replace(/^```json\s*/i, "").replace(/```$/i, "").trim();

    let extracted;
    try {
      extracted = JSON.parse(cleaned);
    } catch {
      throw new Error("La réponse de Gemini n'était pas un JSON valide — réessaie, ou le document est peut-être illisible (scan de mauvaise qualité, etc.).");
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
