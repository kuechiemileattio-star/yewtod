import React, { useEffect, useRef, useState } from "react";
import { UploadCloud, Image as ImageIcon, X } from "lucide-react";
import { T } from "../../theme.js";
import { useStorageUpload } from "../../hooks/useStorageUpload.js";

function filenameFromUrl(url) {
  try {
    return decodeURIComponent(url.split("?")[0].split("/").pop() || "");
  } catch {
    return url;
  }
}

function formatSize(bytes) {
  if (!bytes) return "";
  return `${(bytes / (1024 * 1024)).toFixed(1).replace(".", ",")} Mo`;
}

/**
 * A drag-and-drop upload zone shared by the cover image and the type-specific
 * document field (PDF / CSV) on the simplified work-editor page — both must
 * look and behave identically. Shows the original filename + size picked
 * locally (Supabase Storage renames the file, so that info isn't in the URL).
 */
export default function DropzoneField({ value, onChange, bucket, accept, kind = "file", hint, onFile, onUploadingChange }) {
  const inputRef = useRef(null);
  const [dragOver, setDragOver] = useState(false);
  const [fileMeta, setFileMeta] = useState(null);
  const { upload, uploading, error } = useStorageUpload(bucket);

  useEffect(() => { onUploadingChange?.(uploading); }, [uploading]);

  async function handleFiles(files) {
    const file = files?.[0];
    if (!file) return;
    setFileMeta({ name: file.name, size: file.size });
    onFile?.(file);
    try {
      const url = await upload(file, {});
      onChange(url);
    } catch {
      /* surfaced via `error` below */
    }
  }

  function remove(e) {
    e.stopPropagation();
    onChange("");
    setFileMeta(null);
  }

  const displayName = fileMeta?.name || (value ? filenameFromUrl(value) : "");
  const displaySize = fileMeta ? formatSize(fileMeta.size) : "";
  const noun = kind === "image" ? "une image" : "un fichier";

  return (
    <div
      className={`ytd-dropzone ${dragOver ? "is-dragover" : ""}`}
      onDragOver={e => { e.preventDefault(); setDragOver(true); }}
      onDragLeave={() => setDragOver(false)}
      onDrop={e => { e.preventDefault(); setDragOver(false); handleFiles(e.dataTransfer.files); }}
      onClick={() => inputRef.current?.click()}
      onKeyDown={e => e.key === "Enter" && inputRef.current?.click()}
      role="button" tabIndex={0}
    >
      {kind === "image" && value
        ? <img src={value} alt="" className="ytd-dropzone-thumb" />
        : (kind === "image" ? <ImageIcon size={20} color={T.inkSoft} /> : <UploadCloud size={20} color={T.inkSoft} />)}
      <p>{uploading ? "Envoi…" : <>Glissez {noun} ici, ou <span className="ytd-dropzone-browse">parcourez</span></>}</p>
      {hint && <span className="ytd-dropzone-hint">{hint}</span>}
      {displayName && (
        <div className="ytd-dropzone-file">
          <span>{displayName}{displaySize && ` · ${displaySize}`}</span>
          <button type="button" onClick={remove} aria-label="Retirer le fichier"><X size={13} /></button>
        </div>
      )}
      {error && <span className="ytd-dropzone-error">Échec de l'envoi : {error.message}</span>}
      <input ref={inputRef} type="file" accept={accept} onChange={e => { handleFiles(e.target.files); e.target.value = ""; }} style={{ display: "none" }} />
    </div>
  );
}
