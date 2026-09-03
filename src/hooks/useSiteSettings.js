import { useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

const DEFAULTS = {
  site_name: "Yewtod SS",
  seo_description: "",
  logo_url: "",
  favicon_url: "",
  reflection_of_week: { text: "", author: "Yewtod", date: null },
};

/** Public, read-only access to `site_settings` (key/value table). */
export function useSiteSettings() {
  const [settings, setSettings] = useState(DEFAULTS);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let cancelled = false;
    supabase.from("site_settings").select("key, value").then(({ data, error }) => {
      if (cancelled) return;
      if (!error && data) {
        const merged = { ...DEFAULTS };
        data.forEach(row => { merged[row.key] = row.value; });
        setSettings(merged);
      }
      setLoading(false);
    });
    return () => { cancelled = true; };
  }, []);

  return { settings, loading };
}
