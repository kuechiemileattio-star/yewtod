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
// Sécurité (étape 1, migration 021_rbac_hardening.sql) :
//   - R6 : on ne supprime un membre que si l'on pourrait lui attribuer son rôle
//     actuel (can_assign_role). Un Administrateur ne peut donc plus supprimer
//     le Super Admin ; seul un Super Admin le peut, et jamais lui-même — il en
//     reste donc toujours au moins un.
//
// Deploy:
//   npx supabase functions deploy delete-member --use-api

import { createClient } from "npm:@supabase/supabase-js@2";
import { handlePreflight, jsonResponse } from "../_shared/cors.ts";

const SUPABASE_URL = Deno.env.get("SUPABASE_URL")!;
const SERVICE_ROLE_KEY = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY")!;
const ANON_KEY = Deno.env.get("SUPABASE_ANON_KEY")!;

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

    const { data: canManage, error: permError } = await admin.rpc("has_permission", {
      user_id: caller.id,
      perm_key: "manage_users",
    });
    if (permError) throw permError;
    if (!canManage) throw new HttpError(403, "Vous n'avez pas la permission de gérer les membres.");

    let body: Record<string, unknown>;
    try {
      body = await req.json();
    } catch {
      throw new HttpError(400, "Corps de requête JSON invalide.");
    }
    const userId = typeof body.user_id === "string" ? body.user_id.trim() : "";
    if (!UUID_RE.test(userId)) throw new HttpError(400, "Identifiant de membre invalide.");
    if (userId === caller.id) {
      throw new HttpError(400, "Vous ne pouvez pas supprimer votre propre compte depuis cet écran.");
    }

    // --- R6 : hiérarchie ---------------------------------------------------------
    const { data: target, error: targetError } = await admin
      .from("profiles")
      .select("role_id")
      .eq("id", userId)
      .maybeSingle();
    if (targetError) throw targetError;
    if (!target) throw new HttpError(404, "Membre introuvable.");

    if (target.role_id) {
      const { data: canAssign, error: assignError } = await admin.rpc("can_assign_role", {
        p_actor: caller.id,
        p_role_id: target.role_id,
      });
      if (assignError) throw assignError;
      if (!canAssign) {
        throw new HttpError(403, "Ce membre a un rôle plus élevé que le vôtre : vous ne pouvez pas le supprimer.");
      }
    }

    const { error: deleteError } = await admin.auth.admin.deleteUser(userId);
    if (deleteError) throw deleteError;

    return jsonResponse(req, { ok: true });
  } catch (err) {
    if (err instanceof HttpError) return jsonResponse(req, { error: err.message }, err.status);
    console.error("delete-member error:", err);
    return jsonResponse(req, { error: "Erreur interne lors de la suppression." }, 500);
  }
});
