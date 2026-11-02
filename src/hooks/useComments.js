import { useCallback, useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

/** Visitor comments/avis for one piece of content (any of the 8 "Works"
 * tables, or "books") — list + publish, same shape as useBookReviews. */
export function useContentComments(tableName, contentId) {
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(true);

  const reload = useCallback(async () => {
    if (!tableName || !contentId) { setComments([]); setLoading(false); return; }
    setLoading(true);
    const { data, error } = await supabase
      .from("content_comments")
      .select("*")
      .eq("table_name", tableName)
      .eq("content_id", contentId)
      // Pré-modération (migration 023) : le public ne voit que les avis approuvés.
      // Filtre explicite : un modérateur connecté verrait sinon aussi l'attente.
      .eq("status", "approved")
      .order("created_at", { ascending: false });
    if (!error) setComments((data || []).map(c => ({ id: c.id, author: c.author_name, text: c.text, date: c.created_at })));
    setLoading(false);
  }, [tableName, contentId]);

  useEffect(() => { reload(); }, [reload]);

  /** Envoie un avis : il est enregistré « en attente » et n'apparaîtra
   * qu'après validation par un modérateur (pas de rechargement ici). */
  async function publishComment({ author, text }) {
    const { error } = await supabase.from("content_comments").insert({ table_name: tableName, content_id: contentId, author_name: author, text });
    if (error) throw error;
  }

  return { comments, loading, publishComment };
}
