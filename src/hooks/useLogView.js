import { useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

/** Enregistre un événement (vue / téléchargement) via la fonction
 * log_content_event (migration 023 : cible vérifiée côté base).
 * Une seule fois par session de navigation et par contenu : évite de compter
 * les rafraîchissements et le double montage des composants en mode dev.
 * (Un compteur public anonyme reste indicatif : voir le commentaire de 023.) */
function logEventOncePerSession(tableName, contentId, kind) {
  if (!tableName || !contentId) return;
  const key = `ytd:${kind}:${tableName}:${contentId}`;
  try {
    if (sessionStorage.getItem(key)) return;
    sessionStorage.setItem(key, "1");
  } catch { /* stockage indisponible (navigation privée stricte) : on compte quand même */ }
  supabase.rpc("log_content_event", { p_table: tableName, p_content_id: contentId, p_kind: kind })
    .then(({ error }) => { if (error) console.warn("log_content_event :", error.message); });
}

/** Logs one page-view the first time a given content id is shown in this session. */
export default function useLogView(tableName, contentId) {
  useEffect(() => {
    logEventOncePerSession(tableName, contentId, "view");
  }, [tableName, contentId]);
}

function useCount(rpcName, tableName, contentId) {
  const [count, setCount] = useState(null);
  useEffect(() => {
    if (!tableName || !contentId) return;
    let active = true;
    supabase.rpc(rpcName, { p_table: tableName, p_content_id: contentId }).then(({ data, error }) => {
      if (active && !error) setCount(data);
    });
    return () => { active = false; };
  }, [rpcName, tableName, contentId]);
  return count;
}

/** The public, all-time view count for one piece of content (via the
 * get_public_view_count RPC — content_views rows themselves stay manager-only). */
export function useViewCount(tableName, contentId) {
  return useCount("get_public_view_count", tableName, contentId);
}

/** Same idea as useViewCount, for file downloads (see logDownload below). */
export function useDownloadCount(tableName, contentId) {
  return useCount("get_public_download_count", tableName, contentId);
}

/** Logs one download row — call this when a visitor actually downloads the
 * file (not merely opens the page), so "Téléchargements" reflects real
 * downloads separately from "Vues" (see 015_content_download_tracking.sql). */
export function logDownload(tableName, contentId) {
  logEventOncePerSession(tableName, contentId, "download");
}
