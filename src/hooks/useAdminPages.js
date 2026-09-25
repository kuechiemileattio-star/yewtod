import { useCallback, useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

/** Admin-side read/write for public.pages — an editable-text override per
 * page (matched by `key`), gated by the `manage_pages` permission via RLS. */
export function useAdminPages(key, defaults) {
  const [content, setContent] = useState(defaults);
  const [loading, setLoading] = useState(true);

  const reload = useCallback(async () => {
    setLoading(true);
    const { data } = await supabase.from("pages").select("content").eq("key", key).maybeSingle();
    setContent(data?.content && Object.keys(data.content).length > 0 ? { ...defaults, ...data.content } : defaults);
    setLoading(false);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key]);

  useEffect(() => { reload(); }, [reload]);

  async function save(patch) {
    const next = { ...content, ...patch };
    const { error } = await supabase.from("pages").upsert({ key, content: next }, { onConflict: "key" });
    if (error) throw error;
    setContent(next);
  }

  return { content, loading, save, reload };
}
