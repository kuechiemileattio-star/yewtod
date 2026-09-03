import React, { useRef } from "react";
import { Upload, X, FileText, PlayCircle } from "lucide-react";
import { T } from "../../theme.js";
import { useStorageUpload } from "../../hooks/useStorageUpload.js";
import { inputStyle } from "../Field.jsx";

const EXTERNAL_VIDEO = /youtube\.com|youtu\.be|vimeo\.com/i;

/**
 * A file field that uploads to Supabase Storage and stores the resulting
 * public URL. `kind="image"` shows a live preview; `kind="video"` shows a
 * player for an uploaded file or a badge for an external YouTube/Vimeo link;
 * `kind="file"` shows a filename chip (for PDFs/CSVs). A manual URL input
 * stays available as a fallback for pasting an existing link.
 */
export default function MediaField({ value, onChange, bucket, kind = "image", accept, pathPrefix = "" }) {
  const inputRef = useRef(null);
  const { upload, uploading, error } = useStorageUpload(bucket);

  async function handleFile(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    try {
      const url = await upload(file, { pathPrefix });
      onChange(url);
    } catch {
      /* surfaced via `error` below */
    } finally {
      e.target.value = "";
    }
  }

  return (
    <div style={{ display: "grid", gap: 8 }}>
      {kind === "image" && value && (
        <div style={{ position: "relative", width: 160, height: 100, border: `1px solid ${T.line}`, borderRadius: 6, overflow: "hidden", background: T.paperAlt }}>
          <img src={value} alt="" style={{ width: "100%", height: "100%", objectFit: "cover" }} />
          <button type="button" onClick={() => onChange("")} aria-label="Retirer l'image" style={{ position: "absolute", top: 4, right: 4, display: "grid", placeItems: "center", width: 22, height: 22, border: 0, borderRadius: "50%", background: `${T.ink}CC`, color: T.paper, cursor: "pointer" }}>
            <X size={12} />
          </button>
        </div>
      )}
      {kind === "video" && value && (
        EXTERNAL_VIDEO.test(value) ? (
          <div style={{ display: "inline-flex", alignItems: "center", gap: 8, padding: "8px 12px", border: `1px solid ${T.line}`, borderRadius: 6, background: T.paperAlt, width: "fit-content" }}>
            <PlayCircle size={14} color={T.green} />
            <a href={value} target="_blank" rel="noreferrer" style={{ color: T.ink, fontSize: 12.5, fontFamily: "'Plus Jakarta Sans', sans-serif", textDecoration: "underline" }}>Lien externe (YouTube / Vimeo)</a>
            <button type="button" onClick={() => onChange("")} aria-label="Retirer la vidéo" style={{ border: 0, background: "none", color: T.inkSoft, cursor: "pointer" }}><X size={13} /></button>
          </div>
        ) : (
          <div style={{ position: "relative", width: 220, border: `1px solid ${T.line}`, borderRadius: 6, overflow: "hidden", background: T.ink }}>
            <video src={value} controls style={{ width: "100%", display: "block" }} />
            <button type="button" onClick={() => onChange("")} aria-label="Retirer la vidéo" style={{ position: "absolute", top: 4, right: 4, display: "grid", placeItems: "center", width: 22, height: 22, border: 0, borderRadius: "50%", background: `${T.ink}CC`, color: T.paper, cursor: "pointer" }}>
              <X size={12} />
            </button>
          </div>
        )
      )}
      {kind === "file" && value && (
        <div style={{ display: "inline-flex", alignItems: "center", gap: 8, padding: "8px 12px", border: `1px solid ${T.line}`, borderRadius: 6, background: T.paperAlt, width: "fit-content" }}>
          <FileText size={14} color={T.green} />
          <a href={value} target="_blank" rel="noreferrer" style={{ color: T.ink, fontSize: 12.5, fontFamily: "'Plus Jakarta Sans', sans-serif", textDecoration: "underline" }}>Voir le fichier</a>
          <button type="button" onClick={() => onChange("")} aria-label="Retirer le fichier" style={{ border: 0, background: "none", color: T.inkSoft, cursor: "pointer" }}><X size={13} /></button>
        </div>
      )}
      <div style={{ display: "flex", gap: 8 }}>
        <button type="button" onClick={() => inputRef.current?.click()} disabled={uploading} style={{ display: "inline-flex", alignItems: "center", gap: 7, padding: "9px 12px", border: `1px solid ${T.line}`, background: T.paper, color: T.ink, cursor: uploading ? "wait" : "pointer", fontSize: 12, fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
          <Upload size={14} /> {uploading ? "Envoi…" : value ? "Remplacer" : "Choisir un fichier"}
        </button>
        <input
          type="url"
          value={value || ""}
          onChange={e => onChange(e.target.value)}
          placeholder="ou coller une URL"
          style={{ ...inputStyle, flex: 1 }}
        />
      </div>
      <input ref={inputRef} type="file" accept={accept} onChange={handleFile} style={{ display: "none" }} />
      {error && <span style={{ color: T.red, fontSize: 12 }}>Échec de l'envoi : {error.message}</span>}
    </div>
  );
}
