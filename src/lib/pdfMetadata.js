import { supabase } from "./supabaseClient.js";

// Loaded lazily (see extractPdfMetadata) so this ~1.3 MB parser never ships
// to public-site visitors — only the admin dashboard's report editor needs it.
let pdfjsLibPromise;
function loadPdfjs() {
  if (!pdfjsLibPromise) {
    pdfjsLibPromise = Promise.all([
      import("pdfjs-dist"),
      import("pdfjs-dist/build/pdf.worker.min.mjs?url"),
    ]).then(([pdfjsLib, worker]) => {
      pdfjsLib.GlobalWorkerOptions.workerSrc = worker.default;
      return pdfjsLib;
    });
  }
  return pdfjsLibPromise;
}

async function resolvePageNumber(pdf, dest) {
  try {
    const resolved = typeof dest === "string" ? await pdf.getDestination(dest) : dest;
    if (!resolved) return null;
    const pageIndex = await pdf.getPageIndex(resolved[0]);
    return pageIndex + 1;
  } catch {
    return null;
  }
}

async function flattenOutline(pdf, items) {
  const rows = [];
  for (const item of items || []) {
    rows.push({ title: item.title, page: await resolvePageNumber(pdf, item.dest) });
    if (item.items?.length) rows.push(...(await flattenOutline(pdf, item.items)));
  }
  return rows;
}

// Groups text items into lines by vertical position — pdf.js only gives a
// flat stream of positioned strings, this is the standard cheap heuristic to
// get readable line breaks back out of it (no real paragraph/layout parsing).
function pageTextWithLineBreaks(textContent) {
  let lastY = null;
  let out = "";
  for (const item of textContent.items) {
    const y = item.transform?.[5];
    if (lastY !== null && Math.abs(y - lastY) > 2) out += "\n";
    else if (out && !out.endsWith("\n")) out += " ";
    out += item.str;
    if (y != null) lastY = y;
  }
  return out;
}

/** Renders a page to a PNG snapshot — this is how tables/charts/diagrams (which
 * plain text extraction can't see at all) still end up visible on the site. */
async function renderPageSnapshot(pdf, pageNumber, scale = 1.4) {
  const page = await pdf.getPage(pageNumber);
  const viewport = page.getViewport({ scale });
  const canvas = document.createElement("canvas");
  canvas.width = viewport.width;
  canvas.height = viewport.height;
  await page.render({ canvasContext: canvas.getContext("2d"), viewport }).promise;
  return new Promise(resolve => canvas.toBlob(resolve, "image/png"));
}

async function uploadImageBlob(blob) {
  if (!blob) return null;
  const path = `pdf-pages/${crypto.randomUUID()}.png`;
  const { error } = await supabase.storage.from("media-library").upload(path, blob, { contentType: "image/png", upsert: false });
  if (error) throw error;
  return supabase.storage.from("media-library").getPublicUrl(path).data.publicUrl;
}

// Converts one of pdf.js's raw decoded image objects (shape varies by
// version: an ImageBitmap under `.bitmap`, or a raw RGB/RGBA pixel buffer
// under `.data`) into a drawable PNG blob. Anything pdf.js hands back in a
// color space this doesn't understand (CMYK, indexed palettes...) is skipped
// rather than risked as a garbled image — the full-page snapshot already
// covers it as a fallback.
async function imageObjToBlob(img) {
  if (!img?.width || !img?.height) return null;
  if (img.width < 60 || img.height < 60) return null; // skip icons/bullets/rules, not real photos
  const canvas = document.createElement("canvas");
  canvas.width = img.width;
  canvas.height = img.height;
  const ctx = canvas.getContext("2d");
  if (img.bitmap) {
    ctx.drawImage(img.bitmap, 0, 0);
  } else if (img.data) {
    const pixelCount = img.width * img.height;
    const imageData = ctx.createImageData(img.width, img.height);
    if (img.data.length === pixelCount * 4) {
      imageData.data.set(img.data);
    } else if (img.data.length === pixelCount * 3) {
      for (let src = 0, dst = 0; src < img.data.length; src += 3, dst += 4) {
        imageData.data[dst] = img.data[src];
        imageData.data[dst + 1] = img.data[src + 1];
        imageData.data[dst + 2] = img.data[src + 2];
        imageData.data[dst + 3] = 255;
      }
    } else {
      return null; // unsupported channel count (grayscale/CMYK/indexed) — skip
    }
    ctx.putImageData(imageData, 0, 0);
  } else {
    return null;
  }
  return new Promise(resolve => canvas.toBlob(resolve, "image/png"));
}

/** The embedded photos/logos on one page — actual isolated image objects, not
 * a screenshot of the whole page. Best-effort: any image pdf.js can't hand
 * back cleanly is silently skipped (the full-page snapshot still covers it). */
async function extractPageEmbeddedImages(pdfjsLib, page) {
  let opList;
  try { opList = await page.getOperatorList(); } catch { return []; }
  const paintOps = new Set([pdfjsLib.OPS.paintImageXObject, pdfjsLib.OPS.paintJpegXObject, pdfjsLib.OPS.paintImageXObjectRepeat]);
  const names = [];
  for (let i = 0; i < opList.fnArray.length; i++) {
    if (paintOps.has(opList.fnArray[i])) {
      const name = opList.argsArray[i][0];
      if (name && !names.includes(name)) names.push(name);
    }
  }
  const blobs = [];
  for (const name of names) {
    try {
      const img = await new Promise((resolve, reject) => {
        try { page.objs.get(name, resolve); } catch (err) { reject(err); }
      });
      const blob = await imageObjToBlob(img);
      if (blob) blobs.push(blob);
    } catch { /* one bad image shouldn't drop the rest of the page */ }
  }
  return blobs;
}

/** One pass over every page: its text (for reading), a snapshot image (for
 * anything text can't capture, e.g. vector charts), and its embedded photos
 * (isolated, cleaner than the full-page snapshot when there is one). */
async function extractAllPages(pdfjsLib, pdf, pageCount, onProgress) {
  const texts = [];
  const images = [];
  const embeddedImages = [];
  for (let i = 1; i <= pageCount; i++) {
    const page = await pdf.getPage(i);
    const textContent = await page.getTextContent();
    texts.push(pageTextWithLineBreaks(textContent));

    const embeddedBlobs = await extractPageEmbeddedImages(pdfjsLib, page);
    const embeddedUrls = [];
    for (const blob of embeddedBlobs) embeddedUrls.push(await uploadImageBlob(blob));
    embeddedImages.push(embeddedUrls.filter(Boolean));

    const snapshotBlob = await renderPageSnapshot(pdf, i);
    images.push(await uploadImageBlob(snapshotBlob));
    onProgress?.(i, pageCount);
  }
  return { texts, images, embeddedImages };
}

// Each chapter's text/images are every page from its own start page up to
// (but not including) the next chapter's start page — reconstructed from the
// flat per-page arrays, since the PDF has no other notion of "chapter
// boundaries".
function attachChapterContent(tableOfContents, pageTexts, pageImages, pageEmbeddedImages, pageCount) {
  const withPages = tableOfContents.filter(entry => entry.page != null).sort((a, b) => a.page - b.page);
  tableOfContents.forEach(entry => {
    if (entry.page == null) { entry.content = ""; entry.pageImages = []; entry.embeddedImages = []; return; }
    const sortedIndex = withPages.indexOf(entry);
    const nextEntry = withPages[sortedIndex + 1];
    const endPage = nextEntry ? Math.max(entry.page, nextEntry.page - 1) : pageCount;
    const texts = [];
    const images = [];
    const embedded = [];
    for (let p = entry.page; p <= endPage; p++) {
      if (pageTexts[p - 1]) texts.push(pageTexts[p - 1]);
      if (pageImages[p - 1]) images.push(pageImages[p - 1]);
      if (pageEmbeddedImages[p - 1]?.length) embedded.push(...pageEmbeddedImages[p - 1]);
    }
    entry.content = texts.join("\n\n").trim();
    entry.pageImages = images;
    entry.embeddedImages = embedded;
  });
}

/**
 * Reads a report PDF entirely client-side — page count, table of contents
 * from its embedded outline/bookmarks, each chapter's own text (sliced from
 * the page range up to the next chapter), and a snapshot image of every page
 * in that range (so tables/charts/diagrams, invisible to text extraction,
 * still show up) — so both the Sommaire and the actual reading experience
 * never have to be typed up by hand. Text extraction is a positional
 * heuristic, not real layout parsing, so paragraph breaks won't always match
 * the original. `onProgress(current, total)` reports page-by-page progress
 * since this can take a while on longer documents (one render + one upload
 * per page).
 *
 * Most simple/scanned PDFs have no embedded outline — there's then no title
 * to slice chapters by, so the whole document's text/images are kept as a
 * single "Texte complet du document" entry instead of being dropped entirely.
 */
// Picks out one chapter's text by matching its title against a list of
// keywords (e.g. "introduction", "conclusion") — used on the public detail
// page so it can show just that excerpt instead of the whole report.
// Most PDFs do have a chapter literally titled that way, but titles vary a
// lot ("Avant-propos", "1. Contexte", "En résumé"…) — when nothing matches,
// `fallbackPosition` ("first" or "last") grabs the actual first/last chapter
// of the document instead of giving up, since that's still genuinely that
// report's own content rather than a static placeholder.
export function findChapterText(tableOfContents, keywords, fallbackPosition) {
  if (!tableOfContents?.length) return "";
  const match = tableOfContents.find(entry =>
    keywords.some(kw => entry.title?.toLowerCase().includes(kw))
  );
  if (match?.content?.trim()) return match.content.trim();
  if (fallbackPosition === "first") return tableOfContents[0]?.content?.trim() || "";
  if (fallbackPosition === "last") return tableOfContents[tableOfContents.length - 1]?.content?.trim() || "";
  return "";
}

export async function extractPdfMetadata(file, onProgress) {
  const pdfjsLib = await loadPdfjs();
  const buffer = await file.arrayBuffer();
  const pdf = await pdfjsLib.getDocument({ data: buffer }).promise;
  try {
    const pageCount = pdf.numPages;
    const outline = await pdf.getOutline();
    let tableOfContents = await flattenOutline(pdf, outline);
    const { texts: pageTexts, images: pageImages, embeddedImages: pageEmbeddedImages } = await extractAllPages(pdfjsLib, pdf, pageCount, onProgress);
    if (tableOfContents.length > 0) {
      attachChapterContent(tableOfContents, pageTexts, pageImages, pageEmbeddedImages, pageCount);
    } else {
      const fullText = pageTexts.join("\n\n").trim();
      const allEmbedded = pageEmbeddedImages.flat();
      if (fullText || pageImages.some(Boolean) || allEmbedded.length) {
        tableOfContents = [{ title: "Texte complet du document", page: 1, content: fullText, pageImages: pageImages.filter(Boolean), embeddedImages: allEmbedded }];
      }
    }
    return { pageCount, tableOfContents };
  } finally {
    pdf.destroy();
  }
}
