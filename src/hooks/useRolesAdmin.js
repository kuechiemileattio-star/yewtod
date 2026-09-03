import { useCallback, useEffect, useState } from "react";
import { supabase } from "../lib/supabaseClient.js";

export function useRolesAdmin() {
  const [roles, setRoles] = useState([]);
  const [permissions, setPermissions] = useState([]);
  const [rolePermissions, setRolePermissions] = useState([]); // [{role_id, permission_id}]
  const [profiles, setProfiles] = useState([]);
  const [invitations, setInvitations] = useState([]);
  const [loading, setLoading] = useState(true);

  const reload = useCallback(async () => {
    setLoading(true);
    const [rolesRes, permsRes, rpRes, profilesRes, invitesRes] = await Promise.all([
      supabase.from("roles").select("*").order("created_at", { ascending: true }),
      supabase.from("permissions").select("*").order("key", { ascending: true }),
      supabase.from("role_permissions").select("role_id, permission_id"),
      supabase.from("profiles").select("*, role:roles(id, name)").order("created_at", { ascending: true }),
      supabase.from("invitations").select("*, role:roles(name)").order("sent_at", { ascending: false }),
    ]);
    setRoles(rolesRes.data || []);
    setPermissions(permsRes.data || []);
    setRolePermissions(rpRes.data || []);
    setProfiles(profilesRes.data || []);
    setInvitations(invitesRes.data || []);
    setLoading(false);
  }, []);

  useEffect(() => { reload(); }, [reload]);

  function roleHasPermission(roleId, permissionId) {
    return rolePermissions.some(rp => rp.role_id === roleId && rp.permission_id === permissionId);
  }

  async function createRole(name, description) {
    const { error } = await supabase.from("roles").insert({ name, description });
    if (error) throw error;
    await reload();
  }

  async function deleteRole(id) {
    const { error } = await supabase.from("roles").delete().eq("id", id);
    if (error) throw error;
    await reload();
  }

  async function togglePermission(roleId, permissionId, enabled) {
    if (enabled) {
      const { error } = await supabase.from("role_permissions").insert({ role_id: roleId, permission_id: permissionId });
      if (error) throw error;
    } else {
      const { error } = await supabase.from("role_permissions").delete().eq("role_id", roleId).eq("permission_id", permissionId);
      if (error) throw error;
    }
    await reload();
  }

  async function updateProfile(id, patch) {
    const { error } = await supabase.from("profiles").update(patch).eq("id", id);
    if (error) throw error;
    await reload();
  }

  /** Calls the `invite-user` Edge Function (see supabase/functions/invite-user). */
  async function inviteMember(email, roleId) {
    const { data, error } = await supabase.functions.invoke("invite-user", { body: { email, role_id: roleId } });
    if (error) throw error;
    await reload();
    return data;
  }

  async function revokeInvitation(id) {
    const { error } = await supabase.from("invitations").update({ status: "revoked" }).eq("id", id);
    if (error) throw error;
    await reload();
  }

  return {
    roles, permissions, rolePermissions, profiles, invitations, loading,
    roleHasPermission, createRole, deleteRole, togglePermission, updateProfile, inviteMember, revokeInvitation, reload,
  };
}
