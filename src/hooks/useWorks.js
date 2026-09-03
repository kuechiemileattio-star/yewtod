import { useEffect, useState, useCallback } from "react";
import { supabase } from "../lib/supabaseClient.js";
import { CONTENT_TYPES, getTypeByRouteSlug } from "../lib/contentTypes.js";
import { rowToUi } from "../lib/adapters.js";
import { T } from "../theme.js";

const TONES = [T.green, T.red, T.inkSoft];

function toneFor(id) {
  let hash = 0;
  for (let i = 0; i < String(id).length; i++) hash = (hash * 31 + String(id).charCodeAt(i)) >>> 0;
  return TONES[hash % TONES.length];
}

function joinList(str) {
  return str ? String(str).split("\n").map(s => s.trim()).filter(Boolean).join(", ") : "";
}

function excerptFor(table, ui) {
  switch (table) {
    case "articles": return ui.summary || "";
    case "reports": return ui.executiveSummary || "";
    case "studies": return ui.researchQuestion || "";
    case "research_notes": return ui.mainIdea || "";
    case "documentary_series": return ui.description || "";
    case "documentary_episodes": return ui.summary || "";
    case "experiments": return ui.objective || "";
    case "data_visualizations": return ui.description || "";
    default: return "";
  }
}

function authorFor(table, ui) {
  if (table === "articles") return ui.authorProfile?.fullName || joinList(ui.coAuthors) || "Yewtod SS";
  if (table === "reports" || table === "studies") return joinList(ui.authors) || "Yewtod SS";
  return "Yewtod SS";
}

function readTimeFor(table, ui) {
  if (table === "articles" && ui.readingTimeMinutes) return `${ui.readingTimeMinutes} min`;
  return "";
}

function normalizeWork(table, row) {
  const type = CONTENT_TYPES.find(t => t.table === table);
  const ui = rowToUi(row);
  return {
    ...ui,
    table,
    category: type.label,
    routeSlug: type.routeSlug,
    date: ui.publishedAt || ui.createdAt,
    excerpt: excerptFor(table, ui),
    author: authorFor(table, ui),
    readTime: readTimeFor(table, ui),
    tone: toneFor(ui.id),
  };
}

async function fetchTable(table) {
  const selectCols = table === "articles" ? "*, authorProfile:profiles!author_id(full_name)" : "*";
  const { data, error } = await supabase
    .from(table)
    .select(selectCols)
    .eq("status", "published")
    .order("published_at", { ascending: false });
  if (error) throw error;
  return (data || []).map(row => {
    if (row.authorProfile) row.authorProfile = { fullName: row.authorProfile.full_name };
    return normalizeWork(table, row);
  });
}

/** All published "Works", across the 8 content tables, newest first. */
export function useWorks() {
  const [works, setWorks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const reload = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const results = await Promise.all(CONTENT_TYPES.map(t => fetchTable(t.table)));
      const merged = results.flat().sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0));
      setWorks(merged);
    } catch (err) {
      setError(err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { reload(); }, [reload]);

  return { works, loading, error, reload };
}

/** A single published work, resolved from its /works/:typeSlug/:slug route params. */
export function useWork(routeSlug, slug) {
  const [work, setWork] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    let cancelled = false;
    const type = getTypeByRouteSlug(routeSlug);
    if (!type || !slug) {
      setWork(null);
      setLoading(false);
      return;
    }
    setLoading(true);
    setError(null);
    const selectCols = type.table === "articles" ? "*, authorProfile:profiles!author_id(full_name)" : "*";
    supabase
      .from(type.table)
      .select(selectCols)
      .eq("slug", slug)
      .maybeSingle()
      .then(({ data, error: err }) => {
        if (cancelled) return;
        if (err) { setError(err); setWork(null); }
        else {
          if (data?.authorProfile) data.authorProfile = { fullName: data.authorProfile.full_name };
          setWork(data ? normalizeWork(type.table, data) : null);
        }
        setLoading(false);
      });
    return () => { cancelled = true; };
  }, [routeSlug, slug]);

  return { work, loading, error };
}
