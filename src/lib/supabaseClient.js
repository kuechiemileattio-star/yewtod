import { createClient } from "@supabase/supabase-js";

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL
const supabaseKey = import.meta.env.VITE_SUPABASE_ANON_KEY

if (!supabaseUrl || !supabaseKey) {
  console.warn(
    "Supabase n'est pas configuré : copiez .env.example vers .env.local et renseignez VITE_SUPABASE_URL / VITE_SUPABASE_ANON_KEY."
  );
}

export const supabase = createClient(supabaseUrl || "https://placeholder.supabase.co", supabaseKey || "placeholder");

/** supabase.functions.invoke() only gives a generic "non-2xx status code"
 * message on error — the real reason (thrown as JSON by the function itself)
 * is in the failed response body, reachable via `error.context`. */
export async function readFunctionErrorMessage(error) {
  const fallback = error?.message || "Erreur inconnue";
  try {
    if (error?.context && typeof error.context.json === "function") {
      const body = await error.context.clone().json();
      if (body?.error) return body.error;
    }
  } catch { /* body wasn't JSON or already consumed — fall back below */ }
  return fallback;
}
