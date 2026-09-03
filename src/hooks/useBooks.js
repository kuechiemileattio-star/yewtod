import { useCallback, useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";
import { rowToUi } from "../lib/adapters.js";
import { BOOK_DIFFICULTY_LABELS } from "../lib/contentTypes.js";
import { T } from "../theme.js";

const TONES = [T.green, T.red, T.inkSoft];
function toneFor(id) {
  let hash = 0;
  for (let i = 0; i < String(id).length; i++) hash = (hash * 31 + String(id).charCodeAt(i)) >>> 0;
  return TONES[hash % TONES.length];
}

function normalizeBook(row) {
  const ui = rowToUi(row);
  return { ...ui, difficulty: BOOK_DIFFICULTY_LABELS[ui.difficultyLevel] || ui.difficultyLevel, tone: toneFor(ui.id) };
}

/** All published books. */
export function useBooks() {
  const [books, setBooks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const reload = useCallback(async () => {
    setLoading(true);
    setError(null);
    const { data, error: err } = await supabase
      .from("books")
      .select("*")
      .eq("status", "published")
      .order("published_at", { ascending: false });
    if (err) setError(err); else setBooks((data || []).map(normalizeBook));
    setLoading(false);
  }, []);

  useEffect(() => { reload(); }, [reload]);

  return { books, loading, error, reload };
}

/** A single published book by slug. */
export function useBook(slug) {
  const [book, setBook] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    let cancelled = false;
    if (!slug) { setBook(null); setLoading(false); return; }
    setLoading(true);
    setError(null);
    supabase.from("books").select("*").eq("slug", slug).maybeSingle().then(({ data, error: err }) => {
      if (cancelled) return;
      if (err) { setError(err); setBook(null); } else setBook(data ? normalizeBook(data) : null);
      setLoading(false);
    });
    return () => { cancelled = true; };
  }, [slug]);

  return { book, loading, error };
}

/** Visitor reviews for a book: list + publish. */
export function useBookReviews(bookId) {
  const [reviews, setReviews] = useState([]);
  const [loading, setLoading] = useState(true);

  const reload = useCallback(async () => {
    if (!bookId) { setReviews([]); setLoading(false); return; }
    setLoading(true);
    const { data, error } = await supabase
      .from("book_reviews")
      .select("*")
      .eq("book_id", bookId)
      .order("created_at", { ascending: false });
    if (!error) setReviews((data || []).map(r => ({ id: r.id, author: r.author_name, text: r.text, date: r.created_at })));
    setLoading(false);
  }, [bookId]);

  useEffect(() => { reload(); }, [reload]);

  async function publishReview({ author, text }) {
    const { error } = await supabase.from("book_reviews").insert({ book_id: bookId, author_name: author, text });
    if (error) throw error;
    await reload();
  }

  return { reviews, loading, publishReview };
}
