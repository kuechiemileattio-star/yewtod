import { useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

function safeName(file) {
  const ext = file.name.split(".").pop().toLowerCase().replace(/[^a-z0-9]/g, "");
  const base = crypto.randomUUID();
  return ext ? `${base}.${ext}` : base;
}

/** Uploads a file to a Supabase Storage bucket and returns its public URL. */
export function useStorageUpload(bucket) {
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState(null);

  async function upload(file, { pathPrefix = "" } = {}) {
    setUploading(true);
    setError(null);
    try {
      const path = `${pathPrefix}${safeName(file)}`;
      const { error: uploadError } = await supabase.storage.from(bucket).upload(path, file, { upsert: false });
      if (uploadError) throw uploadError;
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
