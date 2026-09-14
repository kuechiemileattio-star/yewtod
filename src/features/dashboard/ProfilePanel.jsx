import React, { useRef, useState } from "react";
import { User, Mail, FileText, Save, KeyRound, Camera, Pencil, X, Check, AlertCircle, ShieldCheck, CalendarDays, Loader2 } from "lucide-react";
import { T } from "../../theme.js";
import { supabase } from "../../lib/supabaseClient.js";
import { useAuth } from "../../contexts/AuthContext.jsx";
import { useStorageUpload } from "../../hooks/useStorageUpload.js";
import { fmtDate } from "../../lib/contentTypes.js";
import Field, { inputStyle } from "../../components/Field.jsx";
import Btn from "../../components/Btn.jsx";
import StatusPill from "../../components/StatusPill.jsx";

const STATUS_LABELS = { invited: "Invité", active: "Actif", suspended: "Suspendu" };

export default function ProfilePanel() {
  const { profile, reload } = useAuth();
  const fileRef = useRef(null);
  const { upload, uploading } = useStorageUpload("avatars");

  const [editing, setEditing] = useState(false);
  const [fullName, setFullName] = useState(profile?.full_name || "");
  const [bio, setBio] = useState(profile?.bio || "");
  const [avatarUrl, setAvatarUrl] = useState(profile?.avatar_url || "");
  const [avatarError, setAvatarError] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [savingProfile, setSavingProfile] = useState(false);
  const [savingPassword, setSavingPassword] = useState(false);
  const [profileMessage, setProfileMessage] = useState("");
  const [passwordMessage, setPasswordMessage] = useState("");
  const [error, setError] = useState("");
  const [passwordError, setPasswordError] = useState("");

  function startEditing() {
    setFullName(profile?.full_name || "");
    setBio(profile?.bio || "");
    setError("");
    setProfileMessage("");
    setEditing(true);
  }

  async function handleAvatarPick(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    setAvatarError("");
    try {
      const url = await upload(file, { pathPrefix: `${profile?.id}/` });
      setAvatarUrl(url);
      const { error: err } = await supabase.from("profiles").update({ avatar_url: url }).eq("id", profile.id);
      if (err) throw err;
      await reload();
    } catch (err) {
      setAvatarError(err.message || "Échec de l'envoi de la photo.");
    } finally {
      e.target.value = "";
    }
  }

  async function saveProfile(e) {
    e.preventDefault();
    setError("");
    setProfileMessage("");
    setSavingProfile(true);
    try {
      const { error: err } = await supabase
        .from("profiles")
        .update({ full_name: fullName.trim(), bio: bio.trim() })
        .eq("id", profile.id);
      if (err) throw err;
      await reload();
      setEditing(false);
      setProfileMessage("Profil mis à jour avec succès.");
    } catch (err) {
      setError(err.message || "Erreur lors de l'enregistrement.");
    } finally {
      setSavingProfile(false);
    }
  }

  async function changePassword(e) {
    e.preventDefault();
    setPasswordError("");
    setPasswordMessage("");
    if (password.length < 8) { setPasswordError("Le mot de passe doit contenir au moins 8 caractères."); return; }
    if (password !== confirmPassword) { setPasswordError("Les deux mots de passe ne correspondent pas."); return; }
    setSavingPassword(true);
    try {
      const { error: err } = await supabase.auth.updateUser({ password });
      if (err) throw err;
      setPassword("");
      setConfirmPassword("");
      setPasswordMessage("Mot de passe mis à jour.");
    } catch (err) {
      setPasswordError(err.message || "Erreur lors du changement de mot de passe.");
    } finally {
      setSavingPassword(false);
    }
  }

  const initial = (profile?.full_name || profile?.email || "?").slice(0, 1).toUpperCase();

  return (
    <div className="ytd-admin-view ytd-profile-page">
      <div className="ytd-profile-hero">
        <div className="ytd-profile-avatar-wrap">
          <div className="ytd-profile-avatar">
            {avatarUrl ? <img src={avatarUrl} alt="" /> : initial}
            {uploading && <span className="ytd-profile-avatar-uploading"><Loader2 size={20} className="ytd-spin" /></span>}
          </div>
          <button type="button" className="ytd-profile-avatar-btn" onClick={() => fileRef.current?.click()} disabled={uploading} aria-label="Changer la photo de profil" title="Changer la photo de profil">
            <Camera size={15} />
          </button>
          <input ref={fileRef} type="file" accept="image/*" onChange={handleAvatarPick} style={{ display: "none" }} />
        </div>

        <div className="ytd-profile-hero-info">
          <span className="ytd-profile-hero-eyebrow">Mon espace</span>
          <h1>{profile?.full_name || "Membre sans nom"}</h1>
          <div className="ytd-profile-hero-meta">
            <span className="ytd-profile-hero-role">{profile?.role?.name || "Membre"}</span>
            <span><Mail size={13} /> {profile?.email}</span>
            {profile?.created_at && <span><CalendarDays size={13} /> Membre depuis {fmtDate(profile.created_at)}</span>}
          </div>
        </div>

        <div className="ytd-profile-hero-actions">
          {!editing
            ? <Btn variant="green" onClick={startEditing}><Pencil size={15} /> Modifier le profil</Btn>
            : <Btn variant="outline" style={{ borderColor: `${T.paper}66`, color: T.paper }} onClick={() => { setEditing(false); setError(""); }}><X size={15} /> Annuler</Btn>}
        </div>
      </div>

      {avatarError && <p className="ytd-form-message" style={{ color: T.red, marginBottom: 18 }}><AlertCircle size={14} /> {avatarError}</p>}
      {profileMessage && !editing && <p className="ytd-form-message" style={{ color: T.green, marginBottom: 18 }}><Check size={14} /> {profileMessage}</p>}

      {!editing && (
        <div className="ytd-profile-view-grid">
          <section className="ytd-profile-view-card">
            <h3><FileText size={16} color={T.green} /> Bio</h3>
            {profile?.bio ? <p>{profile.bio}</p> : <p className="ytd-profile-empty">Aucune bio renseignée pour le moment. Cliquez sur "Modifier le profil" pour en ajouter une.</p>}
          </section>
          <section className="ytd-profile-view-card">
            <h3><User size={16} color={T.green} /> Informations du compte</h3>
            <div className="ytd-profile-detail-row"><span><Mail size={12} /> Email</span><strong>{profile?.email}</strong></div>
            <div className="ytd-profile-detail-row"><span><ShieldCheck size={12} /> Rôle</span><strong>{profile?.role?.name || "—"}</strong></div>
            <div className="ytd-profile-detail-row"><span>Statut</span><StatusPill statut={STATUS_LABELS[profile?.status]} /></div>
          </section>
        </div>
      )}

      {editing && (
        <section className="ytd-admin-settings-card ytd-profile-editform" style={{ marginBottom: 24 }}>
          <div className="ytd-admin-settings-card-heading"><div><span>Identité</span><h2>Modifier mes informations</h2></div><User size={18} color={T.green} /></div>
          <form onSubmit={saveProfile} style={{ display: "grid", gap: 18 }}>
            <Field label="Nom complet"><input required value={fullName} onChange={e => setFullName(e.target.value)} style={inputStyle} /></Field>
            <Field label="Email" hint="L'email de connexion ne peut pas être modifié ici."><input value={profile?.email || ""} disabled style={{ ...inputStyle, opacity: 0.6 }} /></Field>
            <Field label="Bio"><textarea rows={4} placeholder="Parlez un peu de vous…" value={bio} onChange={e => setBio(e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field>
            {error && <p className="ytd-form-message" style={{ color: T.red }}><AlertCircle size={14} /> {error}</p>}
            <div style={{ display: "flex", gap: 10, flexWrap: "wrap" }}>
              <Btn type="submit" variant="green" style={{ opacity: savingProfile ? 0.7 : 1 }}>
                {savingProfile ? <Loader2 size={15} className="ytd-spin" /> : <Save size={15} />} {savingProfile ? "Enregistrement…" : "Mettre à jour le compte"}
              </Btn>
              <Btn type="button" variant="outline" onClick={() => { setEditing(false); setError(""); }}>Annuler</Btn>
            </div>
          </form>
        </section>
      )}

      <section className="ytd-admin-settings-card">
        <div className="ytd-admin-settings-card-heading"><div><span>Sécurité</span><h2>Mot de passe</h2></div><KeyRound size={18} color={T.green} /></div>
        <form onSubmit={changePassword} style={{ display: "grid", gap: 18 }}>
          <Field label="Nouveau mot de passe"><input type="password" autoComplete="new-password" value={password} onChange={e => setPassword(e.target.value)} style={inputStyle} /></Field>
          <Field label="Confirmer le mot de passe"><input type="password" autoComplete="new-password" value={confirmPassword} onChange={e => setConfirmPassword(e.target.value)} style={inputStyle} /></Field>
          {passwordError && <p className="ytd-form-message" style={{ color: T.red }}><AlertCircle size={14} /> {passwordError}</p>}
          {passwordMessage && <p className="ytd-form-message" style={{ color: T.green }}><Check size={14} /> {passwordMessage}</p>}
          <Btn type="submit" variant="green" style={{ alignSelf: "flex-start", opacity: savingPassword ? 0.7 : 1 }}>
            {savingPassword ? <Loader2 size={15} className="ytd-spin" /> : <KeyRound size={15} />} {savingPassword ? "Mise à jour…" : "Changer le mot de passe"}
          </Btn>
        </form>
      </section>
    </div>
  );
}
