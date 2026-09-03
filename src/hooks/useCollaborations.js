import { useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

/** Public collaboration submission form -> insert into `collaborations` (RLS allows anonymous insert). */
export function useCollaborationSubmit() {
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState(null);

  async function submit({ name, organization, email, subject, type, description }) {
    setSubmitting(true);
    setError(null);
    const { error: err } = await supabase.from("collaborations").insert({
      name,
      organization,
      email,
      subject,
      type,
      description,
    });
    setSubmitting(false);
    if (err) { setError(err); throw err; }
  }

  return { submit, submitting, error };
}
