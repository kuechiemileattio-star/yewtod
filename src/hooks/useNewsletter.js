import { useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

/** Public newsletter signup form → fonction subscribe_newsletter (migration 023).
 * Même réponse que l'adresse soit nouvelle ou déjà inscrite (pas d'énumération). */
export function useNewsletterSubscribe() {
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);

  async function subscribe(email) {
    setSubmitting(true);
    setError(null);
    const { error: err } = await supabase.rpc("subscribe_newsletter", { p_email: email });
    setSubmitting(false);
    if (err) { setError(err); throw err; }
  }

  return { subscribe, submitting, error };
}
