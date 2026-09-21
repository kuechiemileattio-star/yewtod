import React, { useState } from "react";
import { createPortal } from "react-dom";
import { Plus, Search, Edit3, Trash2, Save, X, BookOpen, PlusCircle, DollarSign, Hash, CalendarClock, Check, Sparkles } from "lucide-react";
import { T } from "../../theme.js";
import { BOOK_CATEGORIES, BOOK_DIFFICULTY_LABELS } from "../../lib/contentTypes.js";
import { extractPdfOutline } from "../../lib/pdfMetadata.js";
import { supabase, readFunctionErrorMessage } from "../../lib/supabaseClient.js";
import { useAdminBooks } from "../../hooks/useAdminBooks.js";
import Btn from "../../components/Btn.jsx";
import Field, { inputStyle } from "../../components/Field.jsx";
import StatusPill from "../../components/StatusPill.jsx";
import MediaField from "../../components/dashboard/MediaField.jsx";
import BookPreviewModal from "../../components/dashboard/BookPreviewModal.jsx";

const STATUS_LABELS = { draft: "Brouillon", published: "Publié", scheduled: "Programmé" };
const DIFFICULTY_OPTIONS = Object.entries(BOOK_DIFFICULTY_LABELS);

const MONTHS = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];

// CSS injecté une seule fois — un seul fichier, rien d'autre à importer.
const STYLE_ID = "ytd-admin-books-panel-styles";
if (typeof document !== "undefined" && !document.getElementById(STYLE_ID)) {
  const style = document.createElement("style");
  style.id = STYLE_ID;
  style.textContent = `
@keyframes ytdFadeUp {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes ytdCardIn {
  from { opacity: 0; transform: translateY(14px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes ytdBackdropIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
@keyframes ytdModalIn {
  from { opacity: 0; transform: translateY(18px) scale(0.97); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes ytdRowIn {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes ytdSpin {
  to { transform: rotate(360deg); }
}
@keyframes ytdPulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(46, 125, 78, 0.35); }
  50% { box-shadow: 0 0 0 6px rgba(46, 125, 78, 0); }
}

.ytd-admin-view { animation: ytdFadeUp 380ms cubic-bezier(0.22, 1, 0.36, 1) both; }

.ytd-admin-book-card {
  animation: ytdCardIn 420ms cubic-bezier(0.22, 1, 0.36, 1) both;
  transition: transform 220ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 220ms ease, border-color 220ms ease;
}
.ytd-admin-book-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 16px 32px -20px rgba(20, 18, 14, 0.35);
}
.ytd-admin-book-card:active { transform: translateY(-1px) scale(0.995); }
.ytd-admin-book-cover img { transition: transform 320ms cubic-bezier(0.22, 1, 0.36, 1); }
.ytd-admin-book-card:hover .ytd-admin-book-cover img { transform: scale(1.05); }

.ytd-admin-book-meta-row {
  display: flex; flex-wrap: wrap; gap: 10px; margin-top: 6px;
}
.ytd-admin-book-meta-chip {
  display: inline-flex; align-items: center; gap: 4px;
  font-family: 'IBM Plex Mono', 'Space Mono', monospace; font-size: 10.5px;
  color: ${T.inkSoft}; background: rgba(20,18,14,0.05); border-radius: 3px; padding: 3px 7px;
  transition: background 180ms ease, color 180ms ease;
}
.ytd-admin-book-card:hover .ytd-admin-book-meta-chip { background: rgba(20,18,14,0.08); }

.ytd-admin-icon-button {
  transition: transform 180ms cubic-bezier(0.22, 1, 0.36, 1), background 180ms ease, color 180ms ease;
}
.ytd-admin-icon-button:hover { transform: translateY(-1px) scale(1.06); }
.ytd-admin-icon-button:active { transform: translateY(0) scale(0.96); }

.ytd-admin-filter-scroll button {
  transition: background 200ms ease, color 200ms ease, transform 180ms ease;
}
.ytd-admin-filter-scroll button:hover { transform: translateY(-1px); }
.ytd-admin-filter-scroll button.is-active { animation: ytdFadeUp 220ms ease both; }

.ytd-admin-search input { transition: box-shadow 200ms ease, border-color 200ms ease; }
.ytd-admin-search:focus-within { box-shadow: 0 0 0 3px rgba(46,125,78,0.15); }

.ytd-admin-modal-backdrop {
  animation: ytdBackdropIn 220ms ease both;
  backdrop-filter: blur(3px);
}
.ytd-admin-book-modal {
  animation: ytdModalIn 320ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

.ytd-admin-book-editor input,
.ytd-admin-book-editor select,
.ytd-admin-book-editor textarea {
  transition: box-shadow 180ms ease, border-color 180ms ease, transform 120ms ease;
}
.ytd-admin-book-editor input:focus,
.ytd-admin-book-editor select:focus,
.ytd-admin-book-editor textarea:focus {
  box-shadow: 0 0 0 3px rgba(46,125,78,0.15);
  outline: none;
}

.ytd-admin-chapter-sample-row {
  animation: ytdRowIn 260ms cubic-bezier(0.22, 1, 0.36, 1) both;
}
.ytd-admin-chapter-sample-row button {
  transition: transform 200ms cubic-bezier(0.22, 1, 0.36, 1), color 200ms ease;
}
.ytd-admin-chapter-sample-row button:hover { transform: rotate(90deg); color: ${T.red}; }

.ytd-admin-chapter-sample-add {
  transition: transform 200ms cubic-bezier(0.22, 1, 0.36, 1), background 200ms ease, border-color 200ms ease;
}
.ytd-admin-chapter-sample-add:hover { transform: translateY(-1px); }
.ytd-admin-chapter-sample-add:active { transform: translateY(0) scale(0.98); }

.ytd-admin-book-editor-head button {
  transition: transform 200ms cubic-bezier(0.22, 1, 0.36, 1), background 200ms ease;
}
.ytd-admin-book-editor-head button:hover { transform: rotate(90deg); }

.ytd-admin-save-btn { transition: opacity 200ms ease, transform 200ms ease; }
.ytd-admin-save-btn.is-saving { animation: ytdPulse 1.4s ease-in-out infinite; }
.ytd-admin-spin { display: inline-block; animation: ytdSpin 900ms linear infinite; }

.ytd-admin-error-banner {
  animation: ytdRowIn 220ms ease both;
}

.ytd-admin-similar-picker {
  border: 1px solid rgba(20,18,14,0.12);
  border-radius: 6px;
  padding: 10px;
  background: rgba(20,18,14,0.015);
}
.ytd-admin-similar-chips {
  display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px;
}
.ytd-admin-similar-chip {
  display: inline-flex; align-items: center; gap: 6px;
  background: ${T.green}; color: #fff;
  font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 600;
  border-radius: 999px; padding: 5px 6px 5px 12px;
  animation: ytdRowIn 220ms cubic-bezier(0.22, 1, 0.36, 1) both;
}
.ytd-admin-similar-chip button {
  background: rgba(255,255,255,0.25); border: none; border-radius: 50%;
  width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;
  color: #fff; cursor: pointer; transition: background 180ms ease, transform 180ms ease;
}
.ytd-admin-similar-chip button:hover { background: rgba(255,255,255,0.45); transform: rotate(90deg); }

.ytd-admin-similar-search {
  display: flex; align-items: center; gap: 8px;
  border: 1px solid rgba(20,18,14,0.15); border-radius: 5px;
  padding: 7px 10px; margin-bottom: 8px; background: #fff;
  transition: box-shadow 200ms ease, border-color 200ms ease;
}
.ytd-admin-similar-search:focus-within { box-shadow: 0 0 0 3px rgba(46,125,78,0.15); border-color: ${T.green}; }
.ytd-admin-similar-search input { border: none; outline: none; flex: 1; font-family: 'Inter', sans-serif; font-size: 13px; }

.ytd-admin-similar-list {
  max-height: 190px; overflow-y: auto; display: flex; flex-direction: column; gap: 2px;
}
.ytd-admin-similar-empty { font-family: 'Inter', sans-serif; font-size: 12.5px; color: ${T.inkSoft}; padding: 8px 4px; }
.ytd-admin-similar-row {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 8px; border-radius: 4px; cursor: pointer;
  transition: background 160ms ease, transform 160ms ease;
}
.ytd-admin-similar-row:hover { background: rgba(46,125,78,0.08); transform: translateX(2px); }
.ytd-admin-similar-row.is-checked { background: rgba(46,125,78,0.1); }
.ytd-admin-similar-row input[type="checkbox"] { accent-color: ${T.green}; width: 15px; height: 15px; cursor: pointer; }
.ytd-admin-similar-row-title { font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; color: ${T.ink}; }
.ytd-admin-similar-row-author { font-family: 'Inter', sans-serif; font-size: 11.5px; color: ${T.inkSoft}; margin-left: auto; }

@media (prefers-reduced-motion: reduce) {
  .ytd-admin-view, .ytd-admin-book-card, .ytd-admin-book-cover img, .ytd-admin-icon-button,
  .ytd-admin-filter-scroll button, .ytd-admin-modal-backdrop, .ytd-admin-book-modal,
  .ytd-admin-chapter-sample-row, .ytd-admin-chapter-sample-add, .ytd-admin-book-editor-head button,
  .ytd-admin-save-btn, .ytd-admin-spin, .ytd-admin-error-banner,
  .ytd-admin-similar-chip, .ytd-admin-similar-row {
    animation-duration: 1ms !important;
    transition-duration: 1ms !important;
  }
}
`;
  document.head.appendChild(style);
}

function emptyBook() {
  return {
    title: "", slug: "", author: "", publisher: "", publicationYear: "", category: BOOK_CATEGORIES[0],
    difficultyLevel: "accessible", summary: "", personalReview: "", favoriteQuotes: "", similarBooks: "",
    coverImage: "", purchaseOrReadLink: "", status: "draft", scheduledAt: "",
    pageCount: "", publicationMonth: "", doi: "",
    isbnPaperback: "", pricePaperback: "", buyPaperbackUrl: "",
    isbnEbook: "", priceEbook: "", buyEbookUrl: "", ebookFile: "",
    description: "", components: "", ebookDescription: "", authorBio: "",
    chapterSamples: [],
  };
}

function addChapterSample(book, onChange) {
  onChange({ ...book, chapterSamples: [...(book.chapterSamples || []), { label: "", sizeLabel: "", url: "" }] });
}
function updateChapterSample(book, onChange, index, key, value) {
  const next = (book.chapterSamples || []).map((c, i) => i === index ? { ...c, [key]: value } : c);
  onChange({ ...book, chapterSamples: next });
}
function removeChapterSample(book, onChange, index) {
  onChange({ ...book, chapterSamples: (book.chapterSamples || []).filter((_, i) => i !== index) });
}

function SimilarBooksPicker({ book, allBooks, onChange }) {
  const [query, setQuery] = useState("");
  const selectedTitles = (book.similarBooks || "").split("\n").map(s => s.trim()).filter(Boolean);
  const candidates = (allBooks || []).filter(b => b.id !== book.id);
  const filtered = candidates.filter(b => `${b.title} ${b.author}`.toLowerCase().includes(query.toLowerCase()));

  function toggle(title) {
    const set = new Set(selectedTitles);
    if (set.has(title)) set.delete(title); else set.add(title);
    onChange({ ...book, similarBooks: Array.from(set).join("\n") });
  }

  return (
    <div className="ytd-admin-similar-picker">
      {selectedTitles.length > 0 && (
        <div className="ytd-admin-similar-chips">
          {selectedTitles.map(title => (
            <span key={title} className="ytd-admin-similar-chip">
              {title}
              <button type="button" onClick={() => toggle(title)} aria-label={`Retirer ${title}`}><X size={11} /></button>
            </span>
          ))}
        </div>
      )}
      <label className="ytd-admin-similar-search">
        <Search size={14} color={T.inkSoft} />
        <input value={query} onChange={e => setQuery(e.target.value)} placeholder="Rechercher un livre déjà publié sur la plateforme…" />
      </label>
      <div className="ytd-admin-similar-list">
        {filtered.length === 0 && <p className="ytd-admin-similar-empty">Aucun livre ne correspond à cette recherche.</p>}
        {filtered.map(b => {
          const checked = selectedTitles.includes(b.title);
          return (
            <label key={b.id} className={`ytd-admin-similar-row ${checked ? "is-checked" : ""}`}>
              <input type="checkbox" checked={checked} onChange={() => toggle(b.title)} />
              {checked && <Check size={13} color={T.green} />}
              <span className="ytd-admin-similar-row-title">{b.title}</span>
              <span className="ytd-admin-similar-row-author">{b.author}</span>
            </label>
          );
        })}
      </div>
    </div>
  );
}

function BookEditor({ book, allBooks, onChange, onSave, onClose, saving }) {
  const update = key => e => onChange({ ...book, [key]: e.target.value });
  const [pdfNotice, setPdfNotice] = useState("");
  const [aiExtracting, setAiExtracting] = useState(false);
  const [aiNotice, setAiNotice] = useState("");

  // Same idea as the article/report form's "Analyser avec l'IA" — a real
  // reading of the ebook PDF (description, biographie déduite du contenu,
  // composition) via the extract-pdf-ai Edge Function, on demand.
  async function handleAiExtract() {
    if (!book.ebookFile) return;
    setAiExtracting(true);
    setAiNotice("L'IA lit le document…");
    try {
      const { data, error: fnError } = await supabase.functions.invoke("extract-pdf-ai", { body: { pdfUrl: book.ebookFile } });
      if (fnError) throw new Error(await readFunctionErrorMessage(fnError));
      if (data?.error) throw new Error(data.error);
      const updates = {};
      if (data.title && !book.title) updates.title = data.title;
      if (data.summary && !book.description) updates.description = data.summary;
      if (data.pageCount && !book.pageCount) updates.pageCount = data.pageCount;
      if (data.doi && !book.doi) updates.doi = data.doi;
      if (Array.isArray(data.tableOfContents) && data.tableOfContents.length) {
        updates.components = data.tableOfContents.map(s => s.title).join("\n");
      }
      onChange({ ...book, ...updates });
      setAiNotice(`Analyse IA terminée — ${data.tableOfContents?.length || 0} section(s) détectée(s).`);
    } catch (err) {
      console.error("Échec de l'analyse IA (extract-pdf-ai) :", err);
      setAiNotice(`Échec de l'analyse IA : ${err.message}`);
    } finally {
      setAiExtracting(false);
    }
  }

  // Le sommaire (signets) du PDF déposé donne directement la composition
  // réelle de l'ouvrage — plus besoin de la retaper à la main.
  async function analyzeEbookPdf(fileOrBlob) {
    setPdfNotice("Analyse du PDF…");
    try {
      const { pageCount, tableOfContents } = await extractPdfOutline(fileOrBlob);
      const updates = {};
      if (tableOfContents.length) updates.components = tableOfContents.map(c => c.title).join("\n");
      if (pageCount && !book.pageCount) updates.pageCount = pageCount;
      onChange({ ...book, ...updates });
      setPdfNotice(tableOfContents.length
        ? `Composition extraite automatiquement du PDF (${tableOfContents.length} entrées, ${pageCount} pages).`
        : `${pageCount} pages détectées — ce PDF n'a pas de sommaire intégré (signets), la composition reste à saisir à la main.`);
    } catch {
      setPdfNotice("Impossible d'analyser ce PDF (composition non extraite automatiquement).");
    }
  }

  async function handleEbookPdfSelected(file) {
    await analyzeEbookPdf(file);
  }

  // Un lien collé ne passe jamais par onFile (pas de File local) — il faut
  // retélécharger le PDF nous-mêmes pour pouvoir quand même l'analyser.
  async function handleEbookUrlPasted(url) {
    setPdfNotice("Téléchargement du PDF pour analyse…");
    try {
      const res = await fetch(url);
      if (!res.ok) throw new Error("download failed");
      const blob = await res.blob();
      await analyzeEbookPdf(blob);
    } catch {
      setPdfNotice("Ce lien n'a pas pu être téléchargé pour en extraire automatiquement la composition (le serveur distant bloque peut-être l'accès direct) — à saisir à la main.");
    }
  }

  return createPortal(
    <div className="ytd-admin-modal-backdrop" onMouseDown={e => e.target === e.currentTarget && onClose()}>
      <form className="ytd-admin-book-editor ytd-admin-book-modal" role="dialog" aria-modal="true" onSubmit={onSave}>
        <div className="ytd-admin-book-editor-head">
          <div><span className="ytd-admin-kicker">Fiche en édition</span><h2>{book.title || "Nouveau livre"}</h2></div>
          <button type="button" onClick={onClose} aria-label="Fermer"><X size={18} /></button>
        </div>
        <div className="ytd-admin-book-fields">
          <Field label="Titre"><input required value={book.title} onChange={update("title")} style={inputStyle} /></Field>
          <Field label="Auteur"><input required value={book.author} onChange={update("author")} style={inputStyle} /></Field>
          <Field label="Maison d'édition"><input value={book.publisher || ""} onChange={update("publisher")} style={inputStyle} /></Field>
          <Field label="Année"><input type="number" value={book.publicationYear || ""} onChange={update("publicationYear")} style={inputStyle} /></Field>
          <Field label="Catégorie"><select value={book.category} onChange={update("category")} style={inputStyle}>{BOOK_CATEGORIES.map(c => <option key={c}>{c}</option>)}</select></Field>
          <Field label="Difficulté"><select value={book.difficultyLevel} onChange={update("difficultyLevel")} style={inputStyle}>{DIFFICULTY_OPTIONS.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
        </div>
        <Field label="Slug (URL)" hint="Laissez vide pour générer automatiquement depuis le titre."><input value={book.slug || ""} onChange={update("slug")} placeholder="mon-livre-genial" style={inputStyle} /></Field>
        <Field label="Résumé"><textarea rows={4} value={book.summary || ""} onChange={update("summary")} style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Avis personnel"><textarea required rows={5} value={book.personalReview || ""} onChange={update("personalReview")} style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Citations favorites (une par ligne)"><textarea rows={3} value={book.favoriteQuotes || ""} onChange={update("favoriteQuotes")} style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Ouvrages similaires" hint="Sélectionnez parmi les livres déjà présents sur la plateforme.">
          <SimilarBooksPicker book={book} allBooks={allBooks} onChange={onChange} />
        </Field>
        <Field label="Couverture"><MediaField kind="image" bucket="covers" accept="image/*" value={book.coverImage} onChange={url => onChange({ ...book, coverImage: url })} /></Field>
        <Field label="Lien d'achat ou de consultation"><input type="url" value={book.purchaseOrReadLink || ""} onChange={update("purchaseOrReadLink")} style={inputStyle} /></Field>

        <div className="ytd-admin-book-fields">
          <Field label="Statut"><select value={book.status} onChange={update("status")} style={inputStyle}>{Object.entries(STATUS_LABELS).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></Field>
          {book.status === "scheduled" && (
            <Field label="Date de publication programmée">
              <input type="datetime-local" value={book.scheduledAt || ""} onChange={update("scheduledAt")} style={inputStyle} />
            </Field>
          )}
        </div>

        <div className="ytd-admin-book-fields">
          <Field label="Nombre de pages"><input type="number" value={book.pageCount || ""} onChange={update("pageCount")} style={inputStyle} /></Field>
          <Field label="Mois de publication">
            <select value={book.publicationMonth || ""} onChange={update("publicationMonth")} style={inputStyle}>
              <option value="">—</option>
              {MONTHS.map((m, i) => <option key={m} value={i + 1}>{m}</option>)}
            </select>
          </Field>
          <Field label="DOI"><input value={book.doi || ""} onChange={update("doi")} placeholder="10.xxxx/..." style={inputStyle} /></Field>
        </div>

        <Field label="Description" hint="Texte long affiché dans l'onglet Description de la fiche livre — distinct du résumé."><textarea rows={5} value={book.description || ""} onChange={update("description")} style={{ ...inputStyle, resize: "vertical" }} /></Field>

        <div className="ytd-admin-book-fields">
          <Field label="ISBN (papier)"><input value={book.isbnPaperback || ""} onChange={update("isbnPaperback")} style={inputStyle} /></Field>
          <Field label="Prix (papier, USD)"><input type="number" step="0.01" value={book.pricePaperback || ""} onChange={update("pricePaperback")} style={inputStyle} /></Field>
          <Field label="Lien d'achat (papier)"><input type="url" value={book.buyPaperbackUrl || ""} onChange={update("buyPaperbackUrl")} style={inputStyle} /></Field>
        </div>

        <div className="ytd-admin-book-fields">
          <Field label="ISBN (ebook)"><input value={book.isbnEbook || ""} onChange={update("isbnEbook")} style={inputStyle} /></Field>
          <Field label="Prix (ebook, USD)"><input type="number" step="0.01" value={book.priceEbook || ""} onChange={update("priceEbook")} style={inputStyle} /></Field>
          <Field label="Lien d'achat (ebook)"><input type="url" value={book.buyEbookUrl || ""} onChange={update("buyEbookUrl")} style={inputStyle} /></Field>
        </div>

        <Field label="Version électronique (PDF)" hint={pdfNotice || "À héberger directement sur le site — distinct du lien d'achat externe ci-dessus. Son sommaire (signets) remplit automatiquement la composition ci-dessous."}>
          <MediaField kind="file" bucket="documents" accept="application/pdf" value={book.ebookFile} onChange={url => onChange({ ...book, ebookFile: url })} onFile={handleEbookPdfSelected} onUrl={handleEbookUrlPasted} />
          {book.ebookFile && (
            <div className="ytd-admin-ai-extract">
              <button type="button" onClick={handleAiExtract} disabled={aiExtracting} className="ytd-admin-ai-extract-btn">
                <Sparkles size={14} /> {aiExtracting ? "L'IA analyse le document…" : "Analyser avec l'IA"}
              </button>
              {aiNotice && <span className="ytd-admin-ai-extract-notice">{aiNotice}</span>}
            </div>
          )}
        </Field>
        <Field label="Description de l'ebook"><textarea rows={3} value={book.ebookDescription || ""} onChange={update("ebookDescription")} style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Biographie de l'auteur·e"><textarea rows={4} value={book.authorBio || ""} onChange={update("authorBio")} style={{ ...inputStyle, resize: "vertical" }} /></Field>
        <Field label="Composition du livre (un par ligne)" hint="Remplie automatiquement depuis le sommaire du PDF déposé ci-dessus — modifiable à la main si besoin."><textarea rows={4} value={book.components || ""} onChange={update("components")} style={{ ...inputStyle, resize: "vertical" }} /></Field>

        <Field label="Exemples de chapitres" hint="Un lien de téléchargement par chapitre offert en aperçu (ex : la préface).">
          <div className="ytd-admin-chapter-samples">
            {(book.chapterSamples || []).map((chapter, i) => (
              <div key={i} className="ytd-admin-chapter-sample-row" style={{ animationDelay: `${i * 40}ms` }}>
                <input placeholder="Libellé (ex : Préface)" value={chapter.label || ""} onChange={e => updateChapterSample(book, onChange, i, "label", e.target.value)} style={inputStyle} />
                <input placeholder="Taille (ex : 88 Ko)" value={chapter.sizeLabel || ""} onChange={e => updateChapterSample(book, onChange, i, "sizeLabel", e.target.value)} style={inputStyle} />
                <input type="url" placeholder="Lien du fichier" value={chapter.url || ""} onChange={e => updateChapterSample(book, onChange, i, "url", e.target.value)} style={inputStyle} />
                <button type="button" onClick={() => removeChapterSample(book, onChange, i)} aria-label="Retirer"><X size={15} /></button>
              </div>
            ))}
            <button type="button" className="ytd-admin-chapter-sample-add" onClick={() => addChapterSample(book, onChange)}><PlusCircle size={15} /> Ajouter un exemple de chapitre</button>
          </div>
        </Field>
        <div className="ytd-admin-book-editor-actions">
          <Btn type="submit" variant="green" className="ytd-admin-save-btn" style={{ opacity: saving ? 0.85 : 1, transform: saving ? "scale(0.98)" : "scale(1)" }}>
            {saving ? <span className="ytd-admin-spin"><Save size={15} /></span> : <Save size={15} />} {saving ? "Enregistrement…" : "Enregistrer la fiche"}
          </Btn>
          <Btn type="button" variant="outline" onClick={onClose}>Annuler</Btn>
        </div>
      </form>
    </div>,
    document.body
  );
}











export default function BooksPanel() {
  const { books, loading, createBook, updateBook, deleteBook } = useAdminBooks();
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState("Toutes");
  const [editing, setEditing] = useState(null);
  const [previewing, setPreviewing] = useState(null);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const filtered = books.filter(b => (filter === "Toutes" || b.category === filter) && `${b.title} ${b.author}`.toLowerCase().includes(search.toLowerCase()));

  async function handleSave(e) {
    e.preventDefault();
    setSaving(true);
    setError("");
    try {
      if (editing.id) await updateBook(editing.id, editing);
      else await createBook(editing);
      setEditing(null);
    } catch (err) {
      setError(err.message || "Erreur lors de l'enregistrement.");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(id) {
    if (!window.confirm("Supprimer définitivement ce livre ?")) return;
    await deleteBook(id);
  }

  return (
    <div className="ytd-admin-view">
      <div className="ytd-admin-section-heading">
        <div><span className="ytd-admin-kicker">Bibliothèque éditoriale</span><h1>Livres</h1><p>Créer, modifier et publier les fiches livres — visibles sur le site dès qu'elles sont "Publié".</p></div>
        <Btn variant="green" onClick={() => setEditing(emptyBook())}><Plus size={15} /> Ajouter un livre</Btn>
      </div>

      <div className="ytd-admin-book-toolbar">
        <label className="ytd-admin-search"><Search size={15} /><input value={search} onChange={e => setSearch(e.target.value)} placeholder="Rechercher un livre ou un auteur" /></label>
        <div className="ytd-admin-filter-scroll">{["Toutes", ...BOOK_CATEGORIES].map(c => <button key={c} onClick={() => setFilter(c)} className={filter === c ? "is-active" : ""}>{c}</button>)}</div>
      </div>

      {error && <p className="ytd-admin-error-banner" style={{ color: T.red, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>{error}</p>}

      {loading ? <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</p> : (
        <div className="ytd-admin-books">
          {filtered.map((book, index) => (
            <article key={book.id} className="ytd-admin-book-card" style={{ animationDelay: `${index * 55}ms`, cursor: "pointer" }} onClick={() => setPreviewing(book)} role="button" tabIndex={0} onKeyDown={e => e.key === "Enter" && setPreviewing(book)}>
              <div className="ytd-admin-book-cover" style={{ background: `linear-gradient(145deg, ${T.green}, ${T.greenDeep})` }}>
                {book.coverImage && <img src={book.coverImage} alt="" onError={e => { e.currentTarget.style.display = "none"; }} />}
                <BookOpen size={22} />
              </div>
              <div className="ytd-admin-book-copy">
                <span>{book.category} · {BOOK_DIFFICULTY_LABELS[book.difficultyLevel] || book.difficultyLevel}</span>
                <h2>{book.title}</h2>
                <small>{book.author}</small>
                <p>{book.personalReview}</p>
                <div className="ytd-admin-book-meta-row">
                  {book.pageCount && <span className="ytd-admin-book-meta-chip"><Hash size={10} /> {book.pageCount}pp</span>}
                  {book.pricePaperback && <span className="ytd-admin-book-meta-chip"><DollarSign size={10} /> {book.pricePaperback} papier</span>}
                  {book.priceEbook && <span className="ytd-admin-book-meta-chip"><DollarSign size={10} /> {book.priceEbook} ebook</span>}
                  {book.status === "scheduled" && book.scheduledAt && <span className="ytd-admin-book-meta-chip"><CalendarClock size={10} /> {new Date(book.scheduledAt).toLocaleDateString("fr-FR")}</span>}
                </div>
              </div>
              <div style={{ position: "absolute", top: 12, right: 12, display: "flex", gap: 6 }}>
                <StatusPill statut={STATUS_LABELS[book.status]} />
              </div>
              <div style={{ position: "absolute", bottom: 12, right: 12, display: "flex", gap: 6 }}>
                <button className="ytd-admin-icon-button" onClick={e => { e.stopPropagation(); setEditing(book); }} aria-label={`Modifier ${book.title}`}><Edit3 size={15} /></button>
                <button className="ytd-admin-icon-button" onClick={e => { e.stopPropagation(); handleDelete(book.id); }} aria-label={`Supprimer ${book.title}`}><Trash2 size={15} /></button>
              </div>
            </article>
          ))}
          {filtered.length === 0 && <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Aucun livre ne correspond à cette recherche.</p>}
        </div>
      )}

      {editing && <BookEditor book={editing} allBooks={books} onChange={setEditing} onSave={handleSave} onClose={() => setEditing(null)} saving={saving} />}
      {previewing && <BookPreviewModal book={previewing} onEdit={() => { setEditing(previewing); setPreviewing(null); }} onClose={() => setPreviewing(null)} />}
    </div>
  );
}
