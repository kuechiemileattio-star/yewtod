import React, { useEffect, useState } from "react";
import { useNavigate, useParams, Link } from "react-router-dom";
import { Save, Send, Clock, Search, X, Check, Sparkles, Plus, Trash2, ChevronUp, ChevronDown } from "lucide-react";
import { T } from "../../theme.js";
import { supabase, readFunctionErrorMessage } from "../../lib/supabaseClient.js";
import { rowToUi } from "../../lib/adapters.js";
import { fmtDate, ARTICLE_THEMES, ARTICLE_CONTENT_TYPES } from "../../lib/contentTypes.js";
import { SIMPLE_WORK_TYPES, SIMPLE_WORK_ORDER } from "../../lib/simpleWorkTypes.js";
import { extractPdfMetadata } from "../../lib/pdfMetadata.js";
import { useWorkMutations } from "../../hooks/useWorkMutations.js";
import Field, { inputStyle } from "../Field.jsx";
import Btn from "../Btn.jsx";
import Cover from "../Cover.jsx";
import DropzoneField from "./DropzoneField.jsx";

function emptyForm(config) {
  return {
    title: "", coverImage: "", status: "draft", publishedAt: new Date().toISOString().slice(0, 10),
    [config.summaryField]: "", ...(config.fileField ? { [config.fileField]: "" } : {}),
    ...(config.urlField ? { [config.urlField]: "" } : {}),
    ...(config.table === "documentary_episodes" ? { seriesId: "" } : {}),
    ...(config.table === "reports" ? { tableOfContents: [] } : {}),
    ...(config.table === "articles" ? { content: "", tableOfContents: [], themes: "", contentType: "dossier", tags: "", featured: false, scheduledAt: "", similarArticles: "" } : {}),
  };
}

/** Pick which OTHER published articles show up under "À lire aussi" on this
 * one's public page — matched by title (see ArticleFullPage.jsx), same
 * pattern as the "Ouvrages similaires" picker on the Books form. */
function SimilarArticlesPicker({ value, onChange, options, currentId }) {
  const [query, setQuery] = useState("");
  const selectedTitles = (value || "").split("\n").map(s => s.trim()).filter(Boolean);
  const candidates = options.filter(a => a.id !== currentId);
  const filtered = candidates.filter(a => a.title.toLowerCase().includes(query.toLowerCase()));

  function toggle(title) {
    const set = new Set(selectedTitles);
    if (set.has(title)) set.delete(title); else set.add(title);
    onChange(Array.from(set).join("\n"));
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
        <input value={query} onChange={e => setQuery(e.target.value)} placeholder="Rechercher un article déjà publié…" />
      </label>
      <div className="ytd-admin-similar-list">
        {filtered.length === 0 && <p className="ytd-admin-similar-empty">Aucun article ne correspond à cette recherche.</p>}
        {filtered.map(a => {
          const checked = selectedTitles.includes(a.title);
          return (
            <label key={a.id} className={`ytd-admin-similar-row ${checked ? "is-checked" : ""}`}>
              <input type="checkbox" checked={checked} onChange={() => toggle(a.title)} />
              {checked && <Check size={13} color={T.green} />}
              <span className="ytd-admin-similar-row-title">{a.title}</span>
            </label>
          );
        })}
      </div>
    </div>
  );
}

/** Builds the article body as a list of "sous-titre + contenu" blocks, one
 * added at a time, instead of a single free-text field — this is exactly
 * the {title, page, content} shape already used by the AI/PDF extraction
 * (tableOfContents), so whatever is authored here shows up on the public
 * page as the same interactive Sommaire (see InteractiveSommaire in
 * work-details/shared.jsx): section list on the left, content of the
 * clicked section on the right. */
function ArticleSectionsBuilder({ value, onChange }) {
  const sections = Array.isArray(value) ? value : [];

  function update(i, patch) {
    onChange(sections.map((s, idx) => (idx === i ? { ...s, ...patch } : s)));
  }
  function remove(i) {
    onChange(sections.filter((_, idx) => idx !== i));
  }
  function move(i, dir) {
    const j = i + dir;
    if (j < 0 || j >= sections.length) return;
    const next = [...sections];
    [next[i], next[j]] = [next[j], next[i]];
    onChange(next);
  }
  function add() {
    onChange([...sections, { title: "", page: null, content: "" }]);
  }

  return (
    <div className="ytd-admin-sections-builder">
      {sections.map((s, i) => (
        <div key={i} className="ytd-admin-section-block">
          <div className="ytd-admin-section-block-head">
            <span className="ytd-admin-section-block-n">{String(i + 1).padStart(2, "0")}</span>
            <input
              value={s.title}
              onChange={e => update(i, { title: e.target.value })}
              placeholder="Titre du sous-titre / de la section"
              className="ytd-admin-section-block-title"
            />
            <div className="ytd-admin-section-block-actions">
              <button type="button" onClick={() => move(i, -1)} disabled={i === 0} aria-label="Monter"><ChevronUp size={14} /></button>
              <button type="button" onClick={() => move(i, 1)} disabled={i === sections.length - 1} aria-label="Descendre"><ChevronDown size={14} /></button>
              <button type="button" onClick={() => remove(i)} aria-label="Supprimer cette section" className="is-danger"><Trash2 size={14} /></button>
            </div>
          </div>
          <textarea
            rows={5}
            value={s.content}
            onChange={e => update(i, { content: e.target.value })}
            placeholder="Contenu de cette section…"
            className="ytd-admin-section-block-content"
          />
        </div>
      ))}
      <button type="button" onClick={add} className="ytd-admin-section-add-btn">
        <Plus size={15} /> Ajouter un sous-titre
      </button>
      {sections.length === 0 && <p className="ytd-admin-section-empty">Aucune section pour l'instant — clique sur "Ajouter un sous-titre" pour rédiger le premier bloc de l'article.</p>}
    </div>
  );
}

function SectionHeader({ n, title }) {
  return (
    <div className="ytd-admin-section-header">
      <span className="ytd-admin-section-header-n">{String(n).padStart(2, "0")}</span>
      <span className="ytd-admin-section-header-title">{title}</span>
    </div>
  );
}

function toggleInList(value, item) {
  const list = (value || "").split("\n").map(s => s.trim()).filter(Boolean);
  const next = list.includes(item) ? list.filter(t => t !== item) : [...list, item];
  return next.join("\n");
}

// `tags` is stored as a newline-joined string internally (same convention as
// every other list field, see adapters.js ARRAY_FIELDS) but edited here as a
// comma-separated list, which reads more naturally for tags specifically.
function tagsToInput(tags) {
  return (tags || "").split("\n").map(t => t.trim()).filter(Boolean).join(", ");
}
function inputToTags(value) {
  return value.split(",").map(t => t.trim()).filter(Boolean).join("\n");
}

function SimpleWorkFormInner({ tableKey, id }) {
  const config = SIMPLE_WORK_TYPES[tableKey];
  const isEpisode = config.table === "documentary_episodes";
  const isArticle = config.table === "articles";
  const isReport = config.table === "reports";
  const navigate = useNavigate();
  const { createWork, updateWork, saving } = useWorkMutations();
  const [form, setForm] = useState(() => emptyForm(config));
  const [series, setSeries] = useState([]);
  const [otherArticles, setOtherArticles] = useState([]);
  const [loading, setLoading] = useState(!!id);
  const [error, setError] = useState("");
  const [pdfNotice, setPdfNotice] = useState("");
  const [fileUploading, setFileUploading] = useState(false);
  const [imageUploading, setImageUploading] = useState(false);
  const [aiExtracting, setAiExtracting] = useState(false);
  const [aiNotice, setAiNotice] = useState("");
  const anyUploading = fileUploading || imageUploading;

  useEffect(() => {
    if (!id) return;
    let active = true;
    supabase.from(config.table).select("*").eq("id", id).maybeSingle().then(({ data }) => {
      if (!active) return;
      if (data) {
        const ui = rowToUi(data);
        setForm({ ...emptyForm(config), ...ui, tableOfContents: Array.isArray(ui.tableOfContents) ? ui.tableOfContents : [] });
      }
      setLoading(false);
    });
    return () => { active = false; };
  }, [id, config.table]);

  // For the "similar articles" picker — every published article except this one.
  useEffect(() => {
    if (!isArticle) return;
    supabase.from("articles").select("id, title").eq("status", "published").order("title").then(({ data }) => setOtherArticles(data || []));
  }, [isArticle]);

  // Every episode must belong to a series (NOT NULL in the database) — the
  // only field this simplified form can't drop, unlike the other rich fields.
  useEffect(() => {
    if (!isEpisode) return;
    supabase.from("documentary_series").select("id, title").order("title").then(({ data }) => setSeries(data || []));
  }, [isEpisode]);

  function set(key, value) {
    setForm(prev => ({ ...prev, [key]: value }));
  }

  // Sends the already-uploaded PDF to Gemini (via the extract-pdf-ai Edge
  // Function — the API key can't live in the browser) for a real reading of
  // the document, instead of the client-side pdfjs heuristics in
  // analyzePdf() below. Complements rather than replaces it: this fills in
  // title/résumé/sommaire with actual understanding of the content, on demand.
  async function handleAiExtract() {
    const pdfUrl = form[config.fileField];
    if (!pdfUrl) return;
    setAiExtracting(true);
    setAiNotice("L'IA lit le document…");
    try {
      const { data, error: fnError } = await supabase.functions.invoke("extract-pdf-ai", { body: { pdfUrl } });
      if (fnError) throw new Error(await readFunctionErrorMessage(fnError));
      if (data?.error) throw new Error(data.error);
      setForm(prev => {
        const next = { ...prev };
        if (data.title && !prev.title) next.title = data.title;
        if (data.summary) next[config.summaryField] = data.summary;
        if (data.pageCount) next.pageCount = data.pageCount;
        if (Array.isArray(data.tableOfContents) && data.tableOfContents.length) {
          // Kept as structured data and shown on the public page as a real
          // numbered "Sommaire" (see TableOfContents in shared.jsx) — no
          // longer flattened into `content`, which would just duplicate it.
          next.tableOfContents = data.tableOfContents.map(s => ({ title: s.title, page: s.page ?? null, content: s.summary || "" }));
        }
        return next;
      });
      setAiNotice(`Analyse IA terminée — ${data.tableOfContents?.length || 0} section(s) détectée(s).`);
    } catch (err) {
      console.error("Échec de l'analyse IA (extract-pdf-ai) :", err);
      setAiNotice(`Échec de l'analyse IA : ${err.message}`);
    } finally {
      setAiExtracting(false);
    }
  }

  async function analyzePdf(fileOrBlob) {
    setPdfNotice("Analyse du PDF… (page 1)");
    try {
      const { pageCount, tableOfContents } = await extractPdfMetadata(fileOrBlob, (current, total) => {
        setPdfNotice(`Analyse du PDF… (page ${current} / ${total} — texte et images de chaque page)`);
      });
      setForm(prev => ({ ...prev, pageCount, tableOfContents }));
      setPdfNotice(tableOfContents.length
        ? `Sommaire extrait automatiquement (${tableOfContents.length} entrées, ${pageCount} pages, avec le texte et les images de chaque page).`
        : `${pageCount} pages détectées — ce PDF n'a pas de sommaire intégré (signets).`);
    } catch {
      setPdfNotice("Impossible d'analyser ce PDF (nombre de pages et sommaire non disponibles).");
    }
  }

  async function handlePdfSelected(file) {
    await analyzePdf(file);
  }

  // A pasted PDF link (no local File object) has to be fetched before it can
  // be analyzed the same way — this is what makes Description/Synthèse
  // dynamic even when the PDF was imported "depuis en ligne" instead of
  // dropped from disk.
  async function handlePdfUrlPasted(url) {
    setPdfNotice("Téléchargement du PDF pour analyse…");
    try {
      const res = await fetch(url);
      if (!res.ok) throw new Error("download failed");
      const blob = await res.blob();
      await analyzePdf(blob);
    } catch {
      setPdfNotice("Ce lien n'a pas pu être téléchargé pour en extraire automatiquement le sommaire (le serveur distant bloque peut-être l'accès direct) — la Description et la Synthèse resteront basées sur les champs saisis manuellement.");
    }
  }

  async function trySave(table, payload) {
    if (id) await updateWork(table, id, payload);
    else await createWork(table, payload);
  }

  async function save(nextStatus) {
    if (anyUploading) {
      setError("Merci d'attendre la fin de l'envoi du fichier avant d'enregistrer.");
      return;
    }
    if (isEpisode && !form.seriesId) {
      setError("Choisis la série à laquelle cet épisode appartient.");
      return;
    }
    if (nextStatus === "scheduled" && !form.scheduledAt) {
      setError("Choisis une date et une heure de publication pour programmer cet article.");
      return;
    }
    setError("");
    const payload = { ...form, status: nextStatus };
    if (isArticle || isReport) {
      // Drop sections the user added but never filled in.
      payload.tableOfContents = (form.tableOfContents || []).filter(s => s.title?.trim() || s.content?.trim());
    }
    if (isArticle) {
      payload.tags = inputToTags(form.tags);
      if (nextStatus === "scheduled") {
        payload.publishedAt = form.scheduledAt;
      } else {
        payload.scheduledAt = "";
      }
    }
    try {
      await trySave(config.table, payload);
      navigate(`/dashboard/${config.adminPath}`);
    } catch (err) {
      const msg = err.message || "";
      // The document column itself (pdf_file/csv_file) is missing entirely —
      // no fallback is possible, the table's migration must be run first.
      if (config.fileField && new RegExp(`\\b${config.fileField.replace(/([A-Z])/g, "_$1").toLowerCase()}\\b`, "i").test(msg)) {
        setError(`La colonne "${config.fileField}" n'existe pas encore sur la table ${config.table} : exécute la migration ${config.migrationFile || "correspondante"} dans Supabase, puis réessaie.`);
        return;
      }
      // The page-count / table-of-contents columns only exist once the PDF
      // migration has been run — without it, saving a report/article with a
      // PDF would otherwise fail outright. Fall back to saving without them
      // so publishing never gets blocked by a missing migration.
      if (/page_count|table_of_contents/i.test(msg)) {
        const { pageCount, tableOfContents, ...rest } = payload;
        try {
          await trySave(config.table, rest);
          navigate(`/dashboard/${config.adminPath}`, { state: { warning: `Enregistré, mais le sommaire automatique du PDF n'a pas pu être sauvegardé : exécute la migration ${config.migrationFile || "correspondante"} dans Supabase, puis redépose le PDF pour le récupérer.` } });
          return;
        } catch (err2) {
          setError(err2.message || "Erreur lors de l'enregistrement.");
          return;
        }
      }
      // Same idea for the article taxonomy columns (theme/subtheme/content_type/
      // featured) — added by 011_articles_editorial_taxonomy.sql — so publishing
      // an article isn't blocked if that migration hasn't been run yet.
      if (isArticle && /themes|content_type|featured/i.test(msg)) {
        const { themes, contentType, featured, ...rest } = payload;
        try {
          await trySave(config.table, rest);
          navigate(`/dashboard/${config.adminPath}`, { state: { warning: "Enregistré, mais le thème/type/mise en avant n'ont pas pu être sauvegardés : exécute les migrations 011_articles_editorial_taxonomy.sql et 014_articles_multi_theme.sql dans Supabase, puis réessaie." } });
          return;
        } catch (err2) {
          setError(err2.message || "Erreur lors de l'enregistrement.");
          return;
        }
      }
      setError(err.message || "Erreur lors de l'enregistrement.");
    }
  }

  if (loading) {
    return <div className="ytd-admin-view"><p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</p></div>;
  }

  const summary = form[config.summaryField] || "";
  const excerpt = summary.length > 130 ? `${summary.slice(0, 130).trimEnd()}…` : summary;

  return (
    <div className="ytd-admin-view">
      <p className="ytd-admin-breadcrumb">
        <Link to={`/dashboard/${config.adminPath}`}>{config.plural}</Link> / <strong>{id ? "Modifier" : `Nouveau ${config.singular.toLowerCase()}`}</strong>
      </p>

      {!id && SIMPLE_WORK_ORDER.includes(tableKey) && (
        <div className="ytd-admin-type-tabs">
          {SIMPLE_WORK_ORDER.map(key => {
            const t = SIMPLE_WORK_TYPES[key];
            return (
              <button key={key} type="button" className={key === tableKey ? "is-active" : ""} onClick={() => key !== tableKey && navigate(`/dashboard/${t.adminPath}/nouveau`)}>
                {t.singular}
              </button>
            );
          })}
          <button type="button" onClick={() => navigate("/dashboard/books")}>Livre</button>
        </div>
      )}

      <div className="ytd-admin-editor-page">
        <div className="ytd-admin-editor-form">
          <SectionHeader n={1} title="Identité" />
          <Field label="Titre" required><input required value={form.title} onChange={e => set("title", e.target.value)} style={inputStyle} /></Field>

          {isEpisode && (
            <Field label="Série" required>
              <select required value={form.seriesId || ""} onChange={e => set("seriesId", e.target.value)} style={inputStyle}>
                <option value="" disabled>Choisir une série…</option>
                {series.map(s => <option key={s.id} value={s.id}>{s.title}</option>)}
              </select>
            </Field>
          )}

          <div className="ytd-admin-meta-fields" style={{ gridTemplateColumns: "1fr 1fr" }}>
            <Field label="Catégorie"><input value={config.plural} disabled style={{ ...inputStyle, color: T.inkSoft, background: T.paperAlt }} /></Field>
            <Field label="Date de publication"><input type="date" value={form.publishedAt ? String(form.publishedAt).slice(0, 10) : ""} onChange={e => set("publishedAt", e.target.value)} style={inputStyle} /></Field>
          </div>

          <Field label={config.summaryLabel} hint={isArticle ? `${summary.length}/2900 caractères — la description courte affichée en aperçu.` : undefined}>
            <textarea rows={3} maxLength={isArticle ? 2900 : undefined} value={summary} onChange={e => set(config.summaryField, e.target.value)} style={{ ...inputStyle, resize: "vertical" }} />
          </Field>

          {isArticle && (
            <>
              <SectionHeader n={2} title="Classification" />
              <Field label="Thèmes" hint="Coche tous ceux qui s'appliquent — un article peut relever de plusieurs thèmes à la fois.">
                <div className="ytd-admin-theme-checklist">
                  {ARTICLE_THEMES.map(t => {
                    const checked = (form.themes || "").split("\n").includes(t);
                    return (
                      <label key={t} className={checked ? "is-checked" : ""}>
                        <input type="checkbox" checked={checked} onChange={() => set("themes", toggleInList(form.themes, t))} />
                        {t}
                      </label>
                    );
                  })}
                </div>
              </Field>

              <Field label="Type de contenu" hint="Détermine le gabarit d'affichage de l'article sur le site.">
                <div className="ytd-admin-content-type-picker">
                  {ARTICLE_CONTENT_TYPES.map(ct => (
                    <button key={ct.value} type="button" className={form.contentType === ct.value ? "is-active" : ""} onClick={() => set("contentType", ct.value)} title={ct.hint}>
                      {ct.label}
                    </button>
                  ))}
                </div>
              </Field>

              <Field label="Tags" hint="Séparés par des virgules — relie l'article à plusieurs thématiques à la fois.">
                <input value={tagsToInput(form.tags)} onChange={e => set("tags", inputToTags(e.target.value))} placeholder="ex : inégalités, éducation, Afrique de l'Ouest" style={inputStyle} />
              </Field>

              <label className="ytd-admin-featured-toggle">
                <input type="checkbox" checked={!!form.featured} onChange={e => set("featured", e.target.checked)} />
                Mettre à la une (mis en avant sur la page d'accueil)
              </label>

              <Field label="Articles similaires" hint="Choisis les articles à afficher dans « À lire aussi » sur la page de celui-ci. Sans sélection, le site en propose automatiquement de la même catégorie.">
                <SimilarArticlesPicker value={form.similarArticles} onChange={v => set("similarArticles", v)} options={otherArticles} currentId={id} />
              </Field>

              <SectionHeader n={3} title="Contenu de l'article" />
              <Field
                label="Rédaction par sections"
                hint="Ajoute un sous-titre puis rédige son contenu, section après section, jusqu'à la fin de l'article. Sur la page publique, ces sous-titres formeront le Sommaire à gauche — cliquer sur l'un d'eux affiche son contenu à droite."
              >
                <ArticleSectionsBuilder value={form.tableOfContents} onChange={v => set("tableOfContents", v)} />
              </Field>

              <SectionHeader n={4} title="Médias" />
            </>
          )}

          {isReport && (
            <>
              <SectionHeader n={2} title="Contenu du rapport" />
              <Field
                label="Rédaction par sections"
                hint="Ajoute un sous-titre puis rédige son contenu, section après section. Sur la page publique, ces sous-titres formeront le Sommaire à gauche — cliquer sur l'un d'eux affiche son contenu à droite. Analyser un PDF avec l'IA (plus bas) remplit aussi ces sections automatiquement."
              >
                <ArticleSectionsBuilder value={form.tableOfContents} onChange={v => set("tableOfContents", v)} />
              </Field>

              <SectionHeader n={3} title="Médias" />
            </>
          )}

          {!isArticle && !isReport && <SectionHeader n={2} title="Médias" />}

          {config.urlField && (
            <Field label={config.urlLabel} hint="Sur le site, un aperçu de 2 minutes se lit directement sur la page ; au-delà, un bouton renvoie vers YouTube pour la suite.">
              <input type="url" placeholder="https://www.youtube.com/watch?v=…" value={form[config.urlField] || ""} onChange={e => set(config.urlField, e.target.value)} style={inputStyle} />
            </Field>
          )}

          {config.fileField && (
            <Field label={config.fileLabel} hint={pdfNotice || undefined}>
              <DropzoneField
                kind="file" bucket={config.fileBucket} accept={config.fileAccept} value={form[config.fileField]}
                onChange={v => {
                  // Removing (or replacing via pasted URL) the PDF must drop
                  // its extracted Sommaire too — otherwise the Description/
                  // Synthèse on the public page keep showing the old PDF's
                  // content even once that file is gone.
                  if (config.fileField === "pdfFile") {
                    setForm(prev => ({ ...prev, pdfFile: v, pageCount: v ? prev.pageCount : null, tableOfContents: v ? prev.tableOfContents : [] }));
                    if (!v) setPdfNotice("");
                  } else {
                    set(config.fileField, v);
                  }
                }}
                onFile={config.fileField === "pdfFile" ? handlePdfSelected : undefined}
                onUrl={config.fileField === "pdfFile" ? handlePdfUrlPasted : undefined}
                onUploadingChange={setFileUploading}
              />
              {config.fileField === "pdfFile" && form.pdfFile && (
                <div className="ytd-admin-ai-extract">
                  <button type="button" onClick={handleAiExtract} disabled={aiExtracting} className="ytd-admin-ai-extract-btn">
                    <Sparkles size={14} /> {aiExtracting ? "L'IA analyse le document…" : "Analyser avec l'IA"}
                  </button>
                  {aiNotice && <span className="ytd-admin-ai-extract-notice">{aiNotice}</span>}
                </div>
              )}
            </Field>
          )}

          <Field label="Image de couverture">
            <DropzoneField kind="image" bucket="covers" accept="image/*" value={form.coverImage} onChange={v => set("coverImage", v)} onUploadingChange={setImageUploading} />
          </Field>

          {error && <p className="ytd-admin-form-error">{error}</p>}
        </div>

        <aside className="ytd-admin-editor-preview">
          <span className="ytd-admin-kicker">Aperçu sur le site</span>
          <article className="ytd-card ytd-work-card">
            <Cover tone={T.green} label={config.plural} image={form.coverImage} />
            <div style={{ marginTop: 12 }}>
              {isArticle && (
                <div style={{ display: "flex", flexWrap: "wrap", gap: 6, marginBottom: 8 }}>
                  <span className="ytd-admin-preview-badge">{ARTICLE_CONTENT_TYPES.find(ct => ct.value === form.contentType)?.label}</span>
                  {(form.themes || "").split("\n").filter(Boolean).slice(0, 1).map(t => <span key={t} className="ytd-admin-preview-badge ytd-admin-preview-badge-theme">{t}</span>)}
                </div>
              )}
              <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft }}>{fmtDate(form.publishedAt) || "Date à définir"}</span>
              <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 17, fontWeight: 600, margin: "8px 0", lineHeight: 1.28 }}>{form.title || "Titre du travail"}</h3>
              {excerpt && <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13, color: T.inkSoft, lineHeight: 1.5, margin: 0 }}>{excerpt}</p>}
            </div>
          </article>
          <div className="ytd-admin-editor-preview-actions">
            <Btn variant="green" onClick={() => save("published")} style={{ opacity: saving || anyUploading ? 0.7 : 1, cursor: anyUploading ? "wait" : "pointer" }}><Send size={15} /> Publier</Btn>
            {isArticle && (
              <>
                <input type="datetime-local" value={form.scheduledAt || ""} onChange={e => set("scheduledAt", e.target.value)} style={{ ...inputStyle, fontSize: 13 }} />
                <Btn variant="outline" onClick={() => save("scheduled")} style={{ opacity: saving || anyUploading ? 0.7 : 1, cursor: anyUploading ? "wait" : "pointer" }}><Clock size={15} /> Programmer la publication</Btn>
              </>
            )}
            <Btn variant="outline" onClick={() => save("draft")} style={{ opacity: saving || anyUploading ? 0.7 : 1, cursor: anyUploading ? "wait" : "pointer" }}><Save size={15} /> Enregistrer le brouillon</Btn>
            {anyUploading && <span style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 12 }}>Envoi du fichier en cours…</span>}
          </div>
        </aside>
      </div>
    </div>
  );
}

export default function SimpleWorkForm({ tableKey }) {
  const { id } = useParams();
  return <SimpleWorkFormInner key={`${tableKey}-${id || "new"}`} tableKey={tableKey} id={id} />;
}
