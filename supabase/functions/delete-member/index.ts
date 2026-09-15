// Edge Function: delete-member
//
// Called from the dashboard (Utilisateurs & rôles → supprimer un membre) by
// someone who holds the `manage_users` permission (Super Admin has it by
// default). Runs with the service_role key, which is the only credential
// allowed to call `auth.admin.deleteUser` — this is why member removal
// cannot happen directly from the browser.
//
// Deleting the auth.users row cascades (on delete cascade) to the matching
// public.profiles row, which removes the member's access to every
// permission-gated part of the dashboard immediately.
//
// Deploy:
//   supabase functions deploy delete-member --project-ref <project-ref>

import { createClient } from "npm:@supabase/supabase-js@2";

const SUPABASE_URL = Deno.env.get("SUPABASE_URL");
const SERVICE_ROLE_KEY = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY");
const ANON_KEY = Deno.env.get("SUPABASE_ANON_KEY");

const corsHeaders = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Headers": "authorization, x-client-info, apikey, content-type",
};

Deno.serve(async req => {
  if (req.method === "OPTIONS") return new Response("ok", { headers: corsHeaders });

  try {
    const authHeader = req.headers.get("Authorization");
    if (!authHeader) throw new Error("Missing authorization header");

    // Client scoped to the caller's own JWT — used only to identify who is calling.
    const callerClient = createClient(SUPABASE_URL, ANON_KEY, {
      global: { headers: { Authorization: authHeader } },
    });
    const { data: { user: caller }, error: callerError } = await callerClient.auth.getUser();
    if (callerError || !caller) throw new Error("Invalid session");

    // Admin client (service_role) — bypasses RLS, required for auth.admin.* calls.
    const admin = createClient(SUPABASE_URL, SERVICE_ROLE_KEY);

    const { data: canManage } = await admin.rpc("has_permission", { user_id: caller.id, perm_key: "manage_users" });
    if (!canManage) {
      return new Response(JSON.stringify({ error: "Vous n'avez pas la permission de gérer les membres." }), {
        status: 403,
        headers: { ...corsHeaders, "Content-Type": "application/json" },
      });
    }

    const { user_id } = await req.json();
    if (!user_id) throw new Error("user_id est requis");
    if (user_id === caller.id) throw new Error("Vous ne pouvez pas supprimer votre propre compte depuis cet écran.");

    const { error: deleteError } = await admin.auth.admin.deleteUser(user_id);
    if (deleteError) throw deleteError;

    return new Response(JSON.stringify({ ok: true }), {
      headers: { ...corsHeaders, "Content-Type": "application/json" },
    });
  } catch (err) {
    return new Response(JSON.stringify({ error: err.message }), {
      status: 400,
      headers: { ...corsHeaders, "Content-Type": "application/json" },
    });
  }
});
