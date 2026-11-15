/*
 * Analyse d'un PDF dans le navigateur (cahier § 4.3, § 5.8) : nombre de
 * pages, sommaire depuis les signets, texte de chaque page (progression
 * « page X / Y »). Rien n'est envoyé ailleurs qu'au site.
 * isEvalSupported=false : pdf.js n'utilise pas eval (compatible CSP).
 */
import * as pdfjs from 'pdfjs-dist';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;

export async function analysePdf(file, onProgress = () => {}) {
    const data = new Uint8Array(await file.arrayBuffer());
    const pdf = await pdfjs.getDocument({ data, isEvalSupported: false, disableFontFace: true }).promise;
    const outline = [];
    const items = (await pdf.getOutline()) ?? [];
    for (const item of items.slice(0, 200)) {
        let page = null;
        try {
            const dest = typeof item.dest === 'string' ? await pdf.getDestination(item.dest) : item.dest;
            if (Array.isArray(dest)) page = (await pdf.getPageIndex(dest[0])) + 1;
        } catch (e) { /* signet sans destination */ }
        outline.push({ title: String(item.title ?? '').slice(0, 300), page });
    }
    const texts = [];
    const max = Math.min(pdf.numPages, 300);
    for (let i = 1; i <= max; i++) {
        const page = await pdf.getPage(i);
        const content = await page.getTextContent();
        texts.push(content.items.map((t) => t.str).join(' ').slice(0, 2000));
        onProgress(i, pdf.numPages);
    }
    return { pages: pdf.numPages, outline, texts };
}
