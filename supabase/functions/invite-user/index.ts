// Edge Function: invite-user
//
// Called from the dashboard (Utilisateurs & rôles → Inviter) by a member who
// holds the `invite_users` permission. Runs with the service_role key, which
// is the only credential allowed to call `auth.admin.inviteUserByEmail` —
// this is why the invite flow cannot happen directly from the browser.
//
// Sécurité (étape 1, migration 021_rbac_hardening.sql) :
//   - R5 : l'appelant ne peut attribuer qu'un rôle dont il possède toutes les
//     permissions (Super Admin : seulement par un Super Admin) → can_assign_role.
//   - R4 : le rôle du futur compte vient de la table `invitations`, plus des
//     métadonnées. L'invitation est donc insérée AVANT la création du compte
//     (le trigger on_auth_user_created la lit à cet instant), puis supprimée
//     si l'envoi de l'email échoue.
//
// Deploy:
//   npx supabase functions deploy invite-user --use-api
// (SUPABASE_URL and SUPABASE_SERVICE_ROLE_KEY are already available to every
// Edge Function automatically; no extra secrets needed.)

import { createClient } from "npm:@supabase/supabase-js@2";
import { handlePreflight, jsonResponse } from "../_shared/cors.ts";

const SUPABASE_URL = Deno.env.get("SUPABASE_URL")!;
const SERVICE_ROLE_KEY = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY")!;
const ANON_KEY = Deno.env.get("SUPABASE_ANON_KEY")!;

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const UUID_RE = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

/** Erreur « attendue » : son message est montré tel quel dans le dashboard. */
class HttpError extends Error {
  constructor(public status: number, message: string) {
    super(message);
  }
}

Deno.serve(async req => {
  // CORS : origines du secret ALLOWED_ORIGINS uniquement (étape 2)
  const preflight = handlePreflight(req);
  if (preflight) return preflight;

  try {
    if (req.method !== "POST") throw new HttpError(405, "Méthode non autorisée.");

    const authHeader = req.headers.get("Authorization");
    if (!authHeader) throw new HttpError(401, "Authentification requise.");

    // Client scoped to the caller's own JWT — used only to identify who is calling.
    const callerClient = createClient(SUPABASE_URL, ANON_KEY, {
      global: { headers: { Authorization: authHeader } },
    });
    const { data: { user: caller }, error: callerError } = await callerClient.auth.getUser();
    if (callerError || !caller) throw new HttpError(401, "Session invalide.");

    // Admin client (service_role) — bypasses RLS, required for auth.admin.* calls.
    const admin = createClient(SUPABASE_URL, SERVICE_ROLE_KEY);

    const { data: canInvite, error: permError } = await admin.rpc("has_permission", {
      user_id: caller.id,
      perm_key: "invite_users",
    });
    if (permError) throw permError;
    if (!canInvite) throw new HttpError(403, "Vous n'avez pas la permission d'inviter des membres.");

    // --- Validation des entrées ------------------------------------------------
    let body: Record<string, unknown>;
    try {
      body = await req.json();
    } catch {
      throw new HttpError(400, "Corps de requête JSON invalide.");
    }
    const email = typeof body.email === "string" ? body.email.trim().toLowerCase() : "";
    const roleId = typeof body.role_id === "string" ? body.role_id.trim() : "";
    const fullName = typeof body.full_name === "string" ? body.full_name.trim().slice(0, 120) : "";

    if (!email || email.length > 254 || !EMAIL_RE.test(email)) throw new HttpError(400, "Adresse email invalide.");
    if (!UUID_RE.test(roleId)) throw new HttpError(400, "Rôle invalide.");

    // --- R5 : non-escalade -------------------------------------------------------
    const { data: canAssign, error: assignError } = await admin.rpc("can_assign_role", {
      p_actor: caller.id,
      p_role_id: roleId,
    });
    if (assignError) throw assignError;
    if (!canAssign) {
      throw new HttpError(403, "Vous ne pouvez inviter qu'avec un rôle dont vous possédez toutes les permissions.");
    }

    // --- R4 : l'invitation d'abord ---------------------------------------------
    // Une seule invitation en attente par email : les précédentes sont révoquées.
    const { error: revokeError } = await admin
      .from("invitations")
      .update({ status: "revoked" })
      .eq("email", email)
      .eq("status", "pending");
    if (revokeError) throw revokeError;

    const { data: invitation, error: trackError } = await admin
      .from("invitations")
      .insert({ email, role_id: roleId, invited_by: caller.id, status: "pending" })
      .select("id")
      .single();
    if (trackError) throw trackError;

    // Seul full_name passe en métadonnées : le rôle n'y figure plus.
    const { data: invited, error: inviteError } = await admin.auth.admin.inviteUserByEmail(email, {
      data: fullName ? { full_name: fullName } : {},
    });
    if (inviteError) {
      await admin.from("invitations").delete().eq("id", invitation.id);
      const already = /already|registered|exists/i.test(inviteError.message);
      throw new HttpError(
        already ? 409 : 400,
        already
          ? "Un compte existe déjà pour cet email. Supprimez ce membre avant de le réinviter."
          : `L'envoi de l'invitation a échoué : ${inviteError.message}`,
      );
    }

    return jsonResponse(req, { userId: invited.user.id, invitationId: invitation.id });
  } catch (err) {
    if (err instanceof HttpError) return jsonResponse(req, { error: err.message }, err.status);
    // Erreur inattendue : détail dans les logs Supabase, message générique au client.
    console.error("invite-user error:", err);
    return jsonResponse(req, { error: "Erreur interne lors de l'invitation." }, 500);
  }
});
