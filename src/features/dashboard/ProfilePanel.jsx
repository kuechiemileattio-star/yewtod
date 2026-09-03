import React, { useState } from "react";
import { User, Save, KeyRound } from "lucide-react";
import { T } from "../../theme.js";
import { supabase } from "../../lib/supabaseClient.js";
import { useAuth } from "../../contexts/AuthContext.jsx";
import Field, { inputStyle } from "../../components/Field.jsx";
import Btn from "../../components/Btn.jsx";
import MediaField from "../../components/dashboard/MediaField.jsx";

export default function ProfilePanel() {
  const { profile, reload } = useAuth();
  const [fullName, setFullName] = useState(profile?.full_name || "");
  const [bio, setBio] = useState(profile?.bio || "");
  const [avatarUrl, setAvatarUrl] = useState(profile?.avatar_url || "");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [savingProfile, setSavingProfile] = useState(false);
  const [savingPassword, setSavingPassword] = useState(false);
  const [profileMessage, setProfileMessage] = useState("");
  const [passwordMessage, setPasswordMessage] = useState("");
  const [error, setError] = useState("");
  const [passwordError, setPasswordError] = useState("");

  async function saveProfile(e) {
    e.preventDefault();
    setError("");
    setProfileMessage("");
    setSavingProfile(true);
    try {
      const { error: err } = await supabase
        .from("profiles")
        .update({ full_name: fullName.trim(), bio: bio.trim(), avatar_url: avatarUrl })
        .eq("id", profile.id);
      if (err) throw err;
      await reload();
      setProfileMessage("Profil mis à jour.");
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

  return (
    <div className="ytd-admin-view ytd-admin-settings">
      <div className="ytd-admin-section-heading">
        <div><span className="ytd-admin-kicker">Mon compte</span><h1>Profil</h1><p>Informations affichées dans le dashboard et identifiants de connexion.</p></div>
      </div>

      <section className="ytd-admin-settings-card" style={{ marginBottom: 24 }}>
        <div className="ytd-admin-settings-card-heading"><div><span>Identité</span><h2>Informations personnelles</h2></div><User size={18} color={T.green} /></div>
        <form onSubmit={saveProfile} style={{ display: "grid", gap: 18 }}>
          <Field label="Photo de profil"><MediaField kind="image" bucket="avatars" accept="image/*" value={avatarUrl} onChange={setAvatarUrl} pathPrefix={`${profile?.id}/`} /></Field>
          <Field label="Nom complet"><input required value={fullName} onChange={e => setFullName(e.target.value)} style={inputStyle} /></Field>
          <Field label="Email"><input value={profile?.email || ""} disabled style={{ ...inputStyle, opacity: 0.6 }} /></Field>
          <Field label="Bio"><textarea rows={3} value={bio} onChange={e => setBio(e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field>
          {error && <p style={{ color: T.red, fontFamily: "'Inter', sans-serif", fontSize: 13, margin: 0 }}>{error}</p>}
          {profileMessage && <p style={{ color: T.green, fontFamily: "'Inter', sans-serif", fontSize: 13, margin: 0 }}>{profileMessage}</p>}
          <Btn type="submit" variant="green" style={{ alignSelf: "flex-start", opacity: savingProfile ? 0.7 : 1 }}><Save size={15} /> {savingProfile ? "Enregistrement…" : "Enregistrer"}</Btn>
        </form>
      </section>

      <section className="ytd-admin-settings-card">
        <div className="ytd-admin-settings-card-heading"><div><span>Sécurité</span><h2>Mot de passe</h2></div><KeyRound size={18} color={T.green} /></div>
        <form onSubmit={changePassword} style={{ display: "grid", gap: 18 }}>
          <Field label="Nouveau mot de passe"><input type="password" value={password} onChange={e => setPassword(e.target.value)} style={inputStyle} /></Field>
          <Field label="Confirmer le mot de passe"><input type="password" value={confirmPassword} onChange={e => setConfirmPassword(e.target.value)} style={inputStyle} /></Field>
          {passwordError && <p style={{ color: T.red, fontFamily: "'Inter', sans-serif", fontSize: 13, margin: 0 }}>{passwordError}</p>}
          {passwordMessage && <p style={{ color: T.green, fontFamily: "'Inter', sans-serif", fontSize: 13, margin: 0 }}>{passwordMessage}</p>}
          <Btn type="submit" variant="green" style={{ alignSelf: "flex-start", opacity: savingPassword ? 0.7 : 1 }}><KeyRound size={15} /> {savingPassword ? "Mise à jour…" : "Changer le mot de passe"}</Btn>
        </form>
      </section>
    </div>
  );
}
