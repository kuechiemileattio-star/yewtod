import { useEffect } from "react";
import { supabase } from "../lib/supabaseClient.js";

/** Logs one page-view row the first time a given content id is shown. */
export default function useLogView(tableName, contentId) {
  useEffect(() => {
    if (!tableName || !contentId) return;
    supabase.from("content_views").insert({ table_name: tableName, content_id: contentId });
  }, [tableName, contentId]);
}
