import React, { useEffect, useState } from "react";
import { useNavigate, useParams, Link } from "react-router-dom";
import { Save, Send } from "lucide-react";
import { T } from "../../theme.js";
import { supabase } from "../../lib/supabaseClient.js";
import { rowToUi } from "../../lib/adapters.js";
import { fmtDate } from "../../lib/contentTypes.js";
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
  };
}

function SimpleWorkFormInner({ tableKey, id }) {
  const config = SIMPLE_WORK_TYPES[tableKey];
  const isEpisode = config.table === "documentary_episodes";
  const navigate = useNavigate();
  const { createWork, updateWork, saving } = useWorkMutations();
  const [form, setForm] = useState(() => emptyForm(config));
  const [series, setSeries] = useState([]);
  const [loading, setLoading] = useState(!!id);
  const [error, setError] = useState("");
  const [pdfNotice, setPdfNotice] = useState("");
  const [fileUploading, setFileUploading] = useState(false);
  const [imageUploading, setImageUploading] = useState(false);
  const anyUploading = fileUploading || imageUploading;

  useEffect(() => {
    if (!id) return;
    let active = true;
    supabase.from(config.table).select("*").eq("id", id).maybeSingle().then(({ data }) => {
      if (!active) return;
      if (data) setForm({ ...emptyForm(config), ...rowToUi(data) });
      setLoading(false);
    });
    return () => { active = false; };
  }, [id, config.table]);

  // Every episode must belong to a series (NOT NULL in the database) — the
  // only field this simplified form can't drop, unlike the other rich fields.
  useEffect(() => {
    if (!isEpisode) return;
    supabase.from("documentary_series").select("id, title").order("title").then(({ data }) => setSeries(data || []));
  }, [isEpisode]);

  function set(key, value) {
    setForm(prev => ({ ...prev, [key]: value }));
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
    setError("");
    const payload = { ...form, status: nextStatus };
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
          <Field label="Titre"><input required value={form.title} onChange={e => set("title", e.target.value)} style={inputStyle} /></Field>

          {isEpisode && (
            <Field label="Série">
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

          <Field label={config.summaryLabel}><textarea rows={4} value={summary} onChange={e => set(config.summaryField, e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field>

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
            </Field>
          )}

          <Field label="Image de couverture">
            <DropzoneField kind="image" bucket="covers" accept="image/*" value={form.coverImage} onChange={v => set("coverImage", v)} onUploadingChange={setImageUploading} />
          </Field>

          {error && <p style={{ color: T.red, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>{error}</p>}
        </div>

        <aside className="ytd-admin-editor-preview">
          <span className="ytd-admin-kicker">Aperçu sur le site</span>
          <article className="ytd-card ytd-work-card">
            <Cover tone={T.green} label={config.plural} image={form.coverImage} />
            <div style={{ marginTop: 12 }}>
              <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft }}>{fmtDate(form.publishedAt) || "Date à définir"}</span>
              <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 17, fontWeight: 600, margin: "8px 0", lineHeight: 1.28 }}>{form.title || "Titre du travail"}</h3>
              {excerpt && <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13, color: T.inkSoft, lineHeight: 1.5, margin: 0 }}>{excerpt}</p>}
            </div>
          </article>
          <div className="ytd-admin-editor-preview-actions">
            <Btn variant="green" onClick={() => save("published")} style={{ opacity: saving || anyUploading ? 0.7 : 1, cursor: anyUploading ? "wait" : "pointer" }}><Send size={15} /> Publier</Btn>
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
