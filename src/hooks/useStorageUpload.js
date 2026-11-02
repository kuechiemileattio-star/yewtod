import { useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

/** Buckets privés (migration 022) : pas d'URL publique, on conserve le CHEMIN
 * du fichier ; le dashboard génère une URL signée au moment de l'afficher. */
export const PRIVATE_BUCKETS = new Set(["collaboration-attachments"]);

/** Taille max par bucket (miroir de 022_storage_hardening.sql, pour les messages). */
const MAX_MB = { covers: 5, avatars: 2, "media-library": 20, documents: 50, "collaboration-attachments": 10 };

function safeName(file) {
  const ext = file.name.split(".").pop().toLowerCase().replace(/[^a-z0-9]/g, "");
  const base = crypto.randomUUID();
  return ext ? `${base}.${ext}` : base;
}

/** Traduit les refus de l'API Storage en message compréhensible. */
function friendlyError(err, bucket) {
  const msg = `${err?.message || ""} ${err?.error || ""}`.toLowerCase();
  const status = String(err?.statusCode ?? err?.status ?? "");
  if (status === "413" || msg.includes("maximum allowed size") || msg.includes("too large")) {
    return new Error(`Fichier trop volumineux (maximum ${MAX_MB[bucket] ?? "?"} Mo).`);
  }
  if (msg.includes("mime type") || msg.includes("invalid_mime_type") || status === "415") {
    return new Error("Type de fichier non autorisé pour cet emplacement.");
  }
  if (msg.includes("row-level security") || status === "403") {
    return new Error("Envoi refusé : vous n'avez pas la permission de déposer un fichier ici.");
  }
  return err instanceof Error ? err : new Error(err?.message || "Échec de l'envoi du fichier.");
}

/** Uploads a file to a Supabase Storage bucket and returns its public URL
 * (or, for a private bucket, its storage path). */
export function useStorageUpload(bucket) {
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState(null);

  async function upload(file, { pathPrefix = "" } = {}) {
    setUploading(true);
    setError(null);
    try {
      const path = `${pathPrefix}${safeName(file)}`;
      const { error: uploadError } = await supabase.storage.from(bucket).upload(path, file, { upsert: false });
      if (uploadError) throw friendlyError(uploadError, bucket);
      if (PRIVATE_BUCKETS.has(bucket)) return path;
      const { data } = supabase.storage.from(bucket).getPublicUrl(path);
      return data.publicUrl;
    } catch (err) {
      setError(err);
      throw err;
    } finally {
      setUploading(false);
    }
  }

  return { upload, uploading, error };
}
