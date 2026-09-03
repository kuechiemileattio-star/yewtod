import { useCallback, useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";
import { CONTENT_TYPES } from "../lib/contentTypes.js";
import { rowToUi } from "../lib/adapters.js";

async function fetchAll(type) {
  const { data, error } = await supabase.from(type.table).select("*").order("created_at", { ascending: false });
  if (error) return [];
  return (data || []).map(row => ({ ...rowToUi(row), table: type.table, category: type.label, routeSlug: type.routeSlug }));
}

/** Every row across the 8 content tables, any status — for the dashboard's Publications list. */
export function useAdminWorks() {
  const [works, setWorks] = useState([]);
  const [loading, setLoading] = useState(true);

  const reload = useCallback(async () => {
    setLoading(true);
    const results = await Promise.all(CONTENT_TYPES.map(t => fetchAll(t)));
    const merged = results.flat().sort((a, b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0));
    setWorks(merged);
    setLoading(false);
  }, []);

  useEffect(() => { reload(); }, [reload]);

  return { works, loading, reload };
}
