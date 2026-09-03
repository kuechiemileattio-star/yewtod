import React, { useState } from "react";
import { useNavigate, useLocation, Link } from "react-router-dom";
import { LogIn } from "lucide-react";
import { T } from "../theme.js";
import { PATHS } from "../lib/paths.js";
import { useAuth } from "../contexts/AuthContext.jsx";
import Field, { inputStyle } from "../components/Field.jsx";
import Btn from "../components/Btn.jsx";
import BrandLogo from "../components/BrandLogo.jsx";
import Reveal from "../components/Reveal.jsx";

export default function Login() {
  const { signIn } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setError("");
    setSubmitting(true);
    try {
      await signIn(email, password);
      navigate(location.state?.from || PATHS.dashboard, { replace: true });
    } catch (err) {
      setError("Email ou mot de passe incorrect.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div style={{ minHeight: "calc(100vh - 60px)", display: "flex", alignItems: "center", justifyContent: "center", padding: "40px 24px" }}>
      <Reveal style={{ width: "min(420px, 100%)", padding: 36, border: `1px solid ${T.line}`, background: T.paper, borderRadius: 14, boxShadow: `0 24px 50px -34px ${T.greenDeep}` }}>
        <div style={{ marginBottom: 26 }}><BrandLogo /></div>
        <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.green, textTransform: "uppercase", letterSpacing: "0.08em" }}>Espace privé</span>
        <h1 style={{ fontFamily: "'Fraunces', serif", fontSize: 28, fontWeight: 500, margin: "8px 0 26px" }}>Connexion au dashboard</h1>
        <form onSubmit={handleSubmit} style={{ display: "grid", gap: 18 }}>
          <Field label="Email"><input required type="email" autoComplete="username" value={email} onChange={e => setEmail(e.target.value)} style={inputStyle} /></Field>
          <Field label="Mot de passe"><input required type="password" autoComplete="current-password" value={password} onChange={e => setPassword(e.target.value)} style={inputStyle} /></Field>
          {error && <p style={{ margin: 0, color: T.red, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>{error}</p>}
          <Btn type="submit" variant="green" style={{ justifyContent: "center", opacity: submitting ? 0.7 : 1 }}><LogIn size={15} /> {submitting ? "Connexion…" : "Se connecter"}</Btn>
        </form>
        <p style={{ marginTop: 22, color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 12.5, lineHeight: 1.6 }}>
          Cet espace est réservé aux membres invités. Pas de compte ? <Link to={PATHS.home} style={{ color: T.green }}>Retour au site</Link>.
        </p>
      </Reveal>
    </div>
  );
}
