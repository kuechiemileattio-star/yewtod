import { useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

/** Public newsletter signup form -> insert into `newsletter_subscribers` (RLS allows anonymous insert). */
export function useNewsletterSubscribe() {
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);

  async function subscribe(email) {
    setSubmitting(true);
    setError(null);
    const { error: err } = await supabase.from("newsletter_subscribers").insert({ email });
    setSubmitting(false);
    if (err && err.code !== "23505") { setError(err); throw err; } // 23505 = already subscribed, treat as success
  }

  return { subscribe, submitting, error };
}
