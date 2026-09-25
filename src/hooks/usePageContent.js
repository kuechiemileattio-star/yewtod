import { useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

/** Reads an editable page's content (public.pages, key/value) and merges it
 * over the given defaults — so a page renders correctly with its original
 * hardcoded copy until an admin actually overrides it from the dashboard
 * (see useAdminPages.js), and never breaks if a field was left unedited. */
export function usePageContent(key, defaults) {
  const [content, setContent] = useState(defaults);

  useEffect(() => {
    let active = true;
    supabase.from("pages").select("content").eq("key", key).maybeSingle().then(({ data }) => {
      if (!active) return;
      if (data?.content && Object.keys(data.content).length > 0) {
        setContent({ ...defaults, ...data.content });
      }
    });
    return () => { active = false; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key]);

  return content;
}
