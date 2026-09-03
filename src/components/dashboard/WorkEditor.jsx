import React, { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { Save, X } from "lucide-react";
import { T } from "../../theme.js";
import { supabase } from "../../lib/supabaseClient.js";
import { ARRAY_FIELDS } from "../../lib/adapters.js";
import { useWorkMutations } from "../../hooks/useWorkMutations.js";
import Field, { inputStyle } from "../Field.jsx";
import Btn from "../Btn.jsx";
import MediaField from "./MediaField.jsx";

const STATUS_LABELS = { draft: "Brouillon", published: "Publié", scheduled: "Programmé" };

const SHORT_TEXT_FIELDS = new Set(["subtitle", "theme", "version", "visualizationType", "dataSource"]);
const SINGLE_FILE_FIELDS = {
  pdfFile: { kind: "file", bucket: "documents", accept: ".pdf" },
  csvFile: { kind: "file", bucket: "documents", accept: ".csv" },
  imageFile: { kind: "image", bucket: "media-library", accept: "image/*" },
  videoUrl: { kind: "video", bucket: "media-library", accept: "video/*", hint: "Coller un lien YouTube pour un aperçu de 2 minutes qui renvoie ensuite vers YouTube, ou envoyer un fichier vidéo hébergé directement." },
  trailerUrl: { kind: "video", bucket: "media-library", accept: "video/*", hint: "Coller un lien YouTube pour un aperçu de 2 minutes qui renvoie ensuite vers YouTube, ou envoyer un fichier vidéo hébergé directement." },
};
const PROGRESS_OPTIONS = [["idee", "Idée"], ["en_cours", "En cours"], ["abouti", "Abouti"]];

function emptyFormFor(type) {
  const base = { title: "", coverImage: "", tags: "", status: "draft", publishedAt: "" };
  type.fields.forEach(([key]) => { base[key] = key === "progressStatus" ? "idee" : ""; });
  if (type.table === "documentary_episodes") { base.seriesId = ""; base.episodeNumber = 1; }
  return base;
}

function FieldControl({ fieldKey, label, value, onChange }) {
  if (ARRAY_FIELDS.has(fieldKey)) {
    return <Field label={`${label} (un par ligne)`}><textarea rows={3} value={value || ""} onChange={e => onChange(e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field>;
  }
  if (SINGLE_FILE_FIELDS[fieldKey]) {
    const { kind, bucket, accept, hint } = SINGLE_FILE_FIELDS[fieldKey];
    return (
      <Field label={label}>
        <MediaField kind={kind} bucket={bucket} accept={accept} value={value} onChange={onChange} />
        {hint && <span style={{ display: "block", marginTop: 6, color: T.inkSoft, fontSize: 11, fontFamily: "'Plus Jakarta Sans', sans-serif" }}>{hint}</span>}
      </Field>
    );
  }
  if (fieldKey === "progressStatus") {
    return <Field label={label}><select value={value || "idee"} onChange={e => onChange(e.target.value)} style={inputStyle}>{PROGRESS_OPTIONS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}</select></Field>;
  }
  if (fieldKey === "episodeNumber") {
    return <Field label={label}><input type="number" min={1} value={value || ""} onChange={e => onChange(e.target.value)} style={inputStyle} /></Field>;
  }
  if (SHORT_TEXT_FIELDS.has(fieldKey)) {
    return <Field label={label}><input value={value || ""} onChange={e => onChange(e.target.value)} style={inputStyle} /></Field>;
  }
  return <Field label={label}><textarea rows={4} value={value || ""} onChange={e => onChange(e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field>;
}

export default function WorkEditor({ type, work, onClose, onSaved }) {
  const [form, setForm] = useState(() => work || emptyFormFor(type));
  const [series, setSeries] = useState([]);
  const [error, setError] = useState("");
  const { createWork, updateWork, saving } = useWorkMutations();

  useEffect(() => {
    if (type.table === "documentary_episodes") {
      supabase.from("documentary_series").select("id, title").order("title").then(({ data }) => setSeries(data || []));
    }
  }, [type.table]);

  function set(key, value) {
    setForm(prev => ({ ...prev, [key]: value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    try {
      if (work?.id) await updateWork(type.table, work.id, form);
      else await createWork(type.table, form);
      onSaved();
    } catch (err) {
      setError(err.message || "Erreur lors de l'enregistrement.");
    }
  }

  const isEpisode = type.table === "documentary_episodes";

  return createPortal(
    <div className="ytd-admin-modal-backdrop" onMouseDown={e => e.target === e.currentTarget && onClose()}>
      <form onSubmit={handleSubmit} className="ytd-admin-panel ytd-admin-form ytd-admin-content-modal" role="dialog" aria-modal="true">
        <div className="ytd-admin-book-editor-head">
          <div><span className="ytd-admin-kicker">{type.label}{work?.id ? " · modification" : " · nouveau"}</span><h2>{form.title || "Sans titre"}</h2></div>
          <button type="button" onClick={onClose} aria-label="Fermer"><X size={18} /></button>
        </div>

        <Field label="Titre"><input required value={form.title} onChange={e => set("title", e.target.value)} style={inputStyle} /></Field>

        {isEpisode && (
          <Field label="Série">
            <select required value={form.seriesId || ""} onChange={e => set("seriesId", e.target.value)} style={inputStyle}>
              <option value="" disabled>Choisir une série…</option>
              {series.map(s => <option key={s.id} value={s.id}>{s.title}</option>)}
            </select>
          </Field>
        )}

        {!isEpisode && <Field label="Image de couverture"><MediaField kind="image" bucket="covers" accept="image/*" value={form.coverImage} onChange={url => set("coverImage", url)} /></Field>}

        {type.fields.map(([key, label]) => (
          <FieldControl key={key} fieldKey={key} label={label} value={form[key]} onChange={v => set(key, v)} />
        ))}

        {!isEpisode && <Field label="Mots-clés (un par ligne)"><textarea rows={2} value={form.tags || ""} onChange={e => set("tags", e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field>}

        <div className="ytd-admin-meta-fields">
          <Field label="Statut"><select value={form.status} onChange={e => set("status", e.target.value)} style={inputStyle}>{Object.entries(STATUS_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}</select></Field>
          <Field label="Date de publication"><input type="date" value={form.publishedAt ? String(form.publishedAt).slice(0, 10) : ""} onChange={e => set("publishedAt", e.target.value)} style={inputStyle} /></Field>
        </div>

        {error && <p style={{ color: T.red, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>{error}</p>}

        <div className="ytd-admin-book-editor-actions">
          <Btn type="submit" variant="green" style={{ opacity: saving ? 0.7 : 1 }}><Save size={15} /> {saving ? "Enregistrement…" : "Enregistrer"}</Btn>
          <Btn type="button" variant="outline" onClick={onClose}>Annuler</Btn>
        </div>
      </form>
    </div>,
    document.body
  );
}
