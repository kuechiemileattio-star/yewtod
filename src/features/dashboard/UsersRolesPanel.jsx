import React, { useState } from "react";
import { Plus, Mail, X, ShieldCheck, ShieldAlert, AlertCircle, Check, Loader2, Trash2 } from "lucide-react";
import { T } from "../../theme.js";
import { useRolesAdmin } from "../../hooks/useRolesAdmin.js";
import { useAuth } from "../../contexts/AuthContext.jsx";
import { fmtDate } from "../../lib/contentTypes.js";
import Field, { inputStyle } from "../../components/Field.jsx";
import Btn from "../../components/Btn.jsx";
import StatusPill from "../../components/StatusPill.jsx";

const PROFILE_STATUS_LABELS = { invited: "Invité", active: "Actif", suspended: "Suspendu" };
const INVITATION_STATUS_LABELS = { pending: "En attente", accepted: "Acceptée", expired: "Expirée", revoked: "Révoquée" };

export default function UsersRolesPanel() {
  const {
    roles, permissions, profiles, invitations, loading,
    roleHasPermission, createRole, deleteRole, togglePermission, updateProfile, inviteMember, revokeInvitation, deleteMember,
  } = useRolesAdmin();
  const { profile: currentProfile } = useAuth();

  // A role that carries "manage_users" grants full control of the dashboard
  // (members, roles, permissions) — surfacing this everywhere a role is
  // picked or listed prevents accidentally handing out that level of access.
  const manageUsersPerm = permissions.find(p => p.key === "manage_users");
  const isSensitiveRole = roleId => !!manageUsersPerm && roleHasPermission(roleId, manageUsersPerm.id);
  const permissionCount = roleId => permissions.filter(p => roleHasPermission(roleId, p.id)).length;

  const [newRoleName, setNewRoleName] = useState("");
  const [newRoleDescription, setNewRoleDescription] = useState("");
  const [inviteEmail, setInviteEmail] = useState("");
  const [inviteRoleId, setInviteRoleId] = useState("");
  const [inviteError, setInviteError] = useState("");
  const [inviteSuccess, setInviteSuccess] = useState("");
  const [inviting, setInviting] = useState(false);
  const [deletingId, setDeletingId] = useState(null);
  const [deleteError, setDeleteError] = useState("");

  if (loading) return <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</p>;

  async function handleDeleteMember(member) {
    setDeleteError("");
    if (!window.confirm(`Supprimer définitivement "${member.full_name || member.email}" ? Son profil, son accès et ses droits sur la plateforme seront retirés — action irréversible.`)) return;
    setDeletingId(member.id);
    try {
      await deleteMember(member.id);
    } catch (err) {
      setDeleteError(err.message || "La suppression a échoué.");
    } finally {
      setDeletingId(null);
    }
  }

  async function handleCreateRole(e) {
    e.preventDefault();
    if (!newRoleName.trim()) return;
    await createRole(newRoleName.trim(), newRoleDescription.trim());
    setNewRoleName("");
    setNewRoleDescription("");
  }

  async function handleInvite(e) {
    e.preventDefault();
    setInviteError("");
    setInviteSuccess("");
    if (!inviteEmail.trim() || !inviteRoleId) return;
    setInviting(true);
    try {
      await inviteMember(inviteEmail.trim(), inviteRoleId);
      setInviteSuccess(`Invitation envoyée à ${inviteEmail.trim()}.`);
      setInviteEmail("");
    } catch (err) {
      setInviteError(
        "L'envoi a échoué — la fonction 'invite-user' n'est probablement pas encore déployée sur ce projet Supabase (voir supabase/README.md). " +
        (err.message || "")
      );
    } finally {
      setInviting(false);
    }
  }

  return (
    <div className="ytd-dashboard-new ytd-admin-view">
      <div className="ytd-admin-section-heading">
        <div><span className="ytd-admin-kicker">Organisation</span><h1>Utilisateurs & rôles</h1><p>Membres, rôles personnalisés et invitations — le menu du dashboard s'adapte automatiquement aux permissions de chacun.</p></div>
      </div>

      <section className="ytd-admin-settings-card">
        <div className="ytd-admin-settings-card-heading"><div><span>Équipe</span><h2>Membres</h2></div><span className="ytd-admin-settings-index">{profiles.length}</span></div>
        {deleteError && <p className="ytd-form-message" style={{ color: T.red, fontSize: 12.5, margin: "0 0 12px" }}><AlertCircle size={14} /> {deleteError}</p>}
        <div style={{ display: "grid", gap: 8 }}>
          {profiles.map(p => (
            <div key={p.id} className="ytd-admin-member" style={{ display: "flex", alignItems: "center", gap: 12 }}>
              <div className="ytd-admin-member-avatar">
                {p.avatar_url ? <img src={p.avatar_url} alt="" /> : (p.full_name || p.email || "?").slice(0, 1).toUpperCase()}
              </div>
              <div className="ytd-admin-member-main" style={{ flex: 1, minWidth: 0 }}>
                <strong>{p.full_name || "(sans nom)"}</strong>
                <small>{p.email}</small>
              </div>
              <select value={p.role_id || ""} onChange={e => updateProfile(p.id, { role_id: e.target.value })} style={{ ...inputStyle, width: "auto", padding: "8px 10px", fontSize: 12 }}>
                <option value="" disabled>Rôle…</option>
                {roles.map(r => <option key={r.id} value={r.id}>{isSensitiveRole(r.id) ? `⚠ ${r.name} (accès total)` : r.name}</option>)}
              </select>
              {isSensitiveRole(p.role_id) && <ShieldAlert size={15} color={T.red} aria-label="Accès total au dashboard" title="Ce membre a un rôle avec accès total au dashboard (gestion des membres et des rôles)." />}
              <StatusPill statut={PROFILE_STATUS_LABELS[p.status]} />
              <label className="ytd-switch" title={p.status === "suspended" ? "Réactiver ce membre" : "Suspendre ce membre"}>
                <input
                  type="checkbox"
                  checked={p.status !== "suspended"}
                  onChange={e => updateProfile(p.id, { status: e.target.checked ? "active" : "suspended" })}
                  aria-label={p.status === "suspended" ? "Réactiver" : "Suspendre"}
                />
                <span className="ytd-switch-track"><span className="ytd-switch-thumb" /></span>
              </label>
              {p.id !== currentProfile?.id && (
                <button
                  type="button" onClick={() => handleDeleteMember(p)} disabled={deletingId === p.id}
                  aria-label={`Supprimer ${p.full_name || p.email}`} title="Supprimer ce membre"
                  style={{ border: 0, background: "none", color: T.inkSoft, cursor: deletingId === p.id ? "wait" : "pointer", padding: 4, display: "grid", placeItems: "center" }}
                >
                  {deletingId === p.id ? <Loader2 size={15} className="ytd-spin" /> : <Trash2 size={15} />}
                </button>
              )}
            </div>
          ))}
          {profiles.length === 0 && <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>Aucun membre pour le moment.</p>}
        </div>
      </section>

      <section className="ytd-admin-settings-card">
        <div className="ytd-admin-settings-card-heading"><div><span>Accès</span><h2>Rôles & permissions</h2></div><ShieldCheck size={18} color={T.green} /></div>
        <div className="ytd-admin-table-scroll">
          <table className="ytd-admin-table" style={{ minWidth: 640 }}>
            <thead>
              <tr>
                <th>Permission</th>
                {roles.map(r => (
                  <th key={r.id} style={{ textAlign: "center", background: isSensitiveRole(r.id) ? `${T.red}0e` : undefined }}>
                    <span style={{ display: "inline-flex", alignItems: "center", gap: 4 }}>
                      {r.name}{r.is_system_role && <span title="Rôle système">★</span>}
                      {isSensitiveRole(r.id) && <ShieldAlert size={13} color={T.red} title="Accès total : ce rôle peut gérer les membres et les rôles." />}
                    </span>
                    <span style={{ display: "block", marginTop: 2, fontFamily: "'JetBrains Mono', monospace", fontSize: 10, fontWeight: 400, color: T.inkSoft }}>
                      {permissionCount(r.id)}/{permissions.length}
                    </span>
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {permissions.map(perm => (
                <tr key={perm.id}>
                  <td style={{ whiteSpace: "normal" }}>
                    <strong style={{ display: "block" }}>{perm.label}</strong>
                    <small style={{ color: T.inkSoft }}>{perm.key}</small>
                  </td>
                  {roles.map(role => {
                    const checked = roleHasPermission(role.id, perm.id);
                    const lockGrant = perm.key === "invite_users";
                    return (
                      <td key={role.id} style={{ textAlign: "center", background: isSensitiveRole(role.id) ? `${T.red}08` : undefined }}>
                        <input
                          type="checkbox"
                          checked={checked}
                          onChange={e => togglePermission(role.id, perm.id, e.target.checked)}
                          title={lockGrant ? "Permission sensible : n'accorder qu'aux rôles de confiance" : undefined}
                        />
                      </td>
                    );
                  })}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <p style={{ margin: "10px 0 0", color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 11.5 }}>
          ★ rôle système (Super Admin) — ne peut pas être supprimé. "invite_users" reste décochée par défaut sur les rôles créés pour des invités ; ne l'accordez qu'explicitement.
        </p>
        <form onSubmit={handleCreateRole} style={{ display: "grid", gridTemplateColumns: "1fr 2fr auto", gap: 10, marginTop: 16 }}>
          <input value={newRoleName} onChange={e => setNewRoleName(e.target.value)} placeholder="Nom du rôle" style={inputStyle} />
          <input value={newRoleDescription} onChange={e => setNewRoleDescription(e.target.value)} placeholder="Description" style={inputStyle} />
          <Btn type="submit" variant="green"><Plus size={14} /> Nouveau rôle</Btn>
        </form>
        <div style={{ display: "flex", gap: 8, flexWrap: "wrap", marginTop: 10 }}>
          {roles.filter(r => !r.is_system_role).map(r => (
            <button key={r.id} onClick={() => window.confirm(`Supprimer le rôle "${r.name}" ?`) && deleteRole(r.id)} style={{ display: "inline-flex", alignItems: "center", gap: 5, border: `1px solid ${T.line}`, background: "none", color: T.inkSoft, cursor: "pointer", fontSize: 11, padding: "5px 9px", fontFamily: "'JetBrains Mono', monospace" }}>
              <X size={11} /> Supprimer "{r.name}"
            </button>
          ))}
        </div>
      </section>

      <section className="ytd-admin-settings-card">
        <div className="ytd-admin-settings-card-heading"><div><span>Accès</span><h2>Inviter un membre</h2></div><Mail size={18} color={T.green} /></div>
        <form onSubmit={handleInvite} style={{ display: "grid", gridTemplateColumns: "1fr 1fr auto", gap: 10, alignItems: "end" }}>
          <Field label="Email"><input required type="email" value={inviteEmail} onChange={e => setInviteEmail(e.target.value)} style={inputStyle} /></Field>
          <Field label="Rôle">
            <select required value={inviteRoleId} onChange={e => setInviteRoleId(e.target.value)} style={inputStyle}>
              <option value="" disabled>Choisir…</option>
              {roles.map(r => <option key={r.id} value={r.id}>{isSensitiveRole(r.id) ? `⚠ ${r.name} (accès total)` : r.name}</option>)}
            </select>
          </Field>
          <Btn type="submit" variant="green" style={{ opacity: inviting ? 0.7 : 1 }}>
            {inviting ? <Loader2 size={14} className="ytd-spin" /> : <Plus size={14} />} {inviting ? "Envoi…" : "Inviter"}
          </Btn>
        </form>
        {isSensitiveRole(inviteRoleId) && (
          <p className="ytd-form-message" style={{ color: T.red, fontSize: 12.5, marginTop: 10 }}>
            <ShieldAlert size={14} /> Ce rôle donne un accès total au dashboard (gestion des membres et des rôles) — à réserver aux personnes de confiance.
          </p>
        )}
        {inviteError && <p className="ytd-form-message" style={{ color: T.red, fontSize: 12.5, marginTop: 10 }}><AlertCircle size={14} /> {inviteError}</p>}
        {inviteSuccess && <p className="ytd-form-message" style={{ color: T.green, fontSize: 12.5, marginTop: 10 }}><Check size={14} /> {inviteSuccess}</p>}

        <div style={{ marginTop: 20 }}>
          <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 10, color: T.inkSoft, textTransform: "uppercase" }}>Invitations</span>
          <div style={{ display: "grid", gap: 6, marginTop: 8 }}>
            {invitations.map(inv => (
              <div key={inv.id} style={{ display: "flex", alignItems: "center", gap: 10, padding: "9px 12px", border: `1px solid ${T.line}`, background: T.paperAlt }}>
                <span style={{ flex: 1, fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 12.5 }}>{inv.email}</span>
                <span style={{ color: T.inkSoft, fontFamily: "'JetBrains Mono', monospace", fontSize: 11 }}>{inv.role?.name}</span>
                <span style={{ color: T.inkSoft, fontFamily: "'JetBrains Mono', monospace", fontSize: 11 }}>{fmtDate(inv.sent_at)}</span>
                <StatusPill statut={INVITATION_STATUS_LABELS[inv.status]} />
                {inv.status === "pending" && <button onClick={() => revokeInvitation(inv.id)} style={{ border: 0, background: "none", color: T.inkSoft, cursor: "pointer" }}><X size={14} /></button>}
              </div>
            ))}
            {invitations.length === 0 && <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>Aucune invitation envoyée.</p>}
          </div>
        </div>
      </section>
    </div>
  );
}
