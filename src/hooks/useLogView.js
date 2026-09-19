import { useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

/** Logs one page-view row the first time a given content id is shown. */
export default function useLogView(tableName, contentId) {
  useEffect(() => {
    if (!tableName || !contentId) return;
    supabase.from("content_views").insert({ table_name: tableName, content_id: contentId });
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
  if (!tableName || !contentId) return;
  supabase.from("content_views").insert({ table_name: tableName, content_id: contentId, kind: "download" });
}
