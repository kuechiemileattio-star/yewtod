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

/**
 * Reads a report PDF entirely client-side (page count + table of contents
 * from its embedded outline/bookmarks) so the Sommaire on the public page
 * never has to be typed by hand. Returns an empty tableOfContents when the
 * PDF has no outline — most simple/scanned PDFs don't have one.
 */
export async function extractPdfMetadata(file) {
  const pdfjsLib = await loadPdfjs();
  const buffer = await file.arrayBuffer();
  const pdf = await pdfjsLib.getDocument({ data: buffer }).promise;
  try {
    const pageCount = pdf.numPages;
    const outline = await pdf.getOutline();
    const tableOfContents = await flattenOutline(pdf, outline);
    return { pageCount, tableOfContents };
  } finally {
    pdf.destroy();
  }
}
