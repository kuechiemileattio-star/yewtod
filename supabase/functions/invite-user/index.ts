// Edge Function: invite-user
//
// Called from the dashboard (Utilisateurs & rôles → Inviter) by a member who
// holds the `invite_users` permission. Runs with the service_role key, which
// is the only credential allowed to call `auth.admin.inviteUserByEmail` —
// this is why the invite flow cannot happen directly from the browser.
//
// Deploy:
//   supabase functions deploy invite-user --project-ref <project-ref>
// (SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY are already available to every
// Edge Function automatically; no extra secrets needed.)

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

    const { data: canInvite } = await admin.rpc("has_permission", { user_id: caller.id, perm_key: "invite_users" });
    if (!canInvite) {
      return new Response(JSON.stringify({ error: "Vous n'avez pas la permission d'inviter des membres." }), {
        status: 403,
        headers: { ...corsHeaders, "Content-Type": "application/json" },
      });
    }

    const { email, role_id, full_name } = await req.json();
    if (!email || !role_id) throw new Error("email et role_id sont requis");

    const { data: invited, error: inviteError } = await admin.auth.admin.inviteUserByEmail(email, {
      data: { full_name: full_name || null, role_id, invited_by: caller.id },
    });
    if (inviteError) throw inviteError;

    const { error: trackError } = await admin.from("invitations").insert({
      email,
      role_id,
      invited_by: caller.id,
      status: "pending",
    });
    if (trackError) throw trackError;

    return new Response(JSON.stringify({ userId: invited.user.id }), {
      headers: { ...corsHeaders, "Content-Type": "application/json" },
    });
  } catch (err) {
    return new Response(JSON.stringify({ error: err.message }), {
      status: 400,
      headers: { ...corsHeaders, "Content-Type": "application/json" },
    });
  }
});
