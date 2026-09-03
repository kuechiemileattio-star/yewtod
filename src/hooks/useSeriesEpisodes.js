import { useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";
import { rowToUi } from "../lib/adapters.js";

/** Published episodes belonging to a documentary series, in order. */
export function useSeriesEpisodes(seriesId) {
  const [episodes, setEpisodes] = useState([]);
  const [loading, setLoading] = useState(Boolean(seriesId));

  useEffect(() => {
    let cancelled = false;
    if (!seriesId) { setEpisodes([]); setLoading(false); return; }
    setLoading(true);
    supabase
      .from("documentary_episodes")
      .select("*")
      .eq("series_id", seriesId)
      .eq("status", "published")
      .order("episode_number", { ascending: true })
      .then(({ data, error }) => {
        if (cancelled) return;
        setEpisodes(error ? [] : (data || []).map(rowToUi));
        setLoading(false);
      });
    return () => { cancelled = true; };
  }, [seriesId]);

  return { episodes, loading };
}

/** The parent series of an episode (for the "retour à la série" link). */
export function useEpisodeSeries(seriesId) {
  const [series, setSeries] = useState(null);

  useEffect(() => {
    let cancelled = false;
    if (!seriesId) { setSeries(null); return; }
    supabase.from("documentary_series").select("id, slug, title").eq("id", seriesId).maybeSingle().then(({ data }) => {
      if (!cancelled) setSeries(data || null);
    });
    return () => { cancelled = true; };
  }, [seriesId]);

  return series;
}
