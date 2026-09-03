import React, { useState } from "react";
import { Navigate, useNavigate } from "react-router-dom";
import { Check } from "lucide-react";
import { T } from "../theme.js";
import { PATHS } from "../lib/paths.js";
import { supabase } from "../lib/supabaseClient.js";
import { useAuth } from "../contexts/AuthContext.jsx";
import Field, { inputStyle } from "../components/Field.jsx";
import Btn from "../components/Btn.jsx";
import BrandLogo from "../components/BrandLogo.jsx";
import Reveal from "../components/Reveal.jsx";

export default function Onboarding() {
  const { session, profile, loading, reload } = useAuth();
  const navigate = useNavigate();
  const [fullName, setFullName] = useState(profile?.full_name || "");
  const [bio, setBio] = useState(profile?.bio || "");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  if (loading) return null;
  if (!session || !profile) return <Navigate to="/login" replace />;
  if (profile.status === "active") return <Navigate to={PATHS.dashboard} replace />;

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    if (!fullName.trim()) { setError("Merci de renseigner votre nom."); return; }
    setSubmitting(true);
    try {
      if (password) {
        const { error: pwError } = await supabase.auth.updateUser({ password });
        if (pwError) throw pwError;
      }
      const { error: profileError } = await supabase
        .from("profiles")
        .update({ full_name: fullName.trim(), bio: bio.trim(), status: "active" })
        .eq("id", profile.id);
      if (profileError) throw profileError;
      await reload();
      navigate(PATHS.dashboard, { replace: true });
    } catch (err) {
      setError(err.message || "Une erreur est survenue, merci de réessayer.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div style={{ minHeight: "calc(100vh - 60px)", display: "flex", alignItems: "center", justifyContent: "center", padding: "40px 24px" }}>
      <Reveal style={{ width: "min(480px, 100%)", padding: 36, border: `1px solid ${T.line}`, background: T.paper, borderRadius: 14, boxShadow: `0 24px 50px -34px ${T.greenDeep}` }}>
        <div style={{ marginBottom: 26 }}><BrandLogo /></div>
        <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.green, textTransform: "uppercase", letterSpacing: "0.08em" }}>Bienvenue</span>
        <h1 style={{ fontFamily: "'Fraunces', serif", fontSize: 26, fontWeight: 500, margin: "8px 0 10px" }}>Complétez votre profil</h1>
        <p style={{ margin: "0 0 26px", color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13.5, lineHeight: 1.6 }}>
          Dernière étape avant d'accéder à votre espace, limité aux fonctionnalités de votre rôle.
        </p>
        <form onSubmit={handleSubmit} style={{ display: "grid", gap: 18 }}>
          <Field label="Nom complet"><input required value={fullName} onChange={e => setFullName(e.target.value)} style={inputStyle} /></Field>
          <Field label="Bio (facultatif)"><textarea rows={3} value={bio} onChange={e => setBio(e.target.value)} style={{ ...inputStyle, resize: "vertical" }} /></Field>
          <Field label="Nouveau mot de passe (facultatif si déjà défini)"><input type="password" value={password} onChange={e => setPassword(e.target.value)} style={inputStyle} /></Field>
          {error && <p style={{ margin: 0, color: T.red, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>{error}</p>}
          <Btn type="submit" variant="green" style={{ justifyContent: "center", opacity: submitting ? 0.7 : 1 }}><Check size={15} /> {submitting ? "Enregistrement…" : "Accéder au dashboard"}</Btn>
        </form>
      </Reveal>
    </div>
  );
}
