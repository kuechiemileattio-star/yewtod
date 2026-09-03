import { useCallback, useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";
import { rowToUi, uiToRow } from "../lib/adapters.js";

function normalize(row) {
  return rowToUi(row);
}

/** Admin view of `books` — sees every status, can create/update/delete. */
export function useAdminBooks() {
  const [books, setBooks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const reload = useCallback(async () => {
    setLoading(true);
    const { data, error: err } = await supabase.from("books").select("*").order("created_at", { ascending: false });
    if (err) setError(err); else setBooks((data || []).map(normalize));
    setLoading(false);
  }, []);

  useEffect(() => { reload(); }, [reload]);

  async function createBook(uiFields) {
    const row = uiToRow(uiFields);
    const { error: err } = await supabase.from("books").insert(row);
    if (err) throw err;
    await reload();
  }

  async function updateBook(id, uiFields) {
    const row = uiToRow(uiFields);
    delete row.id;
    const { error: err } = await supabase.from("books").update(row).eq("id", id);
    if (err) throw err;
    await reload();
  }

  async function deleteBook(id) {
    const { error: err } = await supabase.from("books").delete().eq("id", id);
    if (err) throw err;
    await reload();
  }

  return { books, loading, error, reload, createBook, updateBook, deleteBook };
}
