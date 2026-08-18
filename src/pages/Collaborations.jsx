import React, { useState } from "react";
import { ArrowRight, Image as ImageIcon, Check } from "lucide-react";
import { T } from "../theme.js";
import { COLLAB_TYPES } from "../data.js";
import NodeMark from "../components/NodeMark.jsx";
import Reveal from "../components/Reveal.jsx";
import Btn from "../components/Btn.jsx";
import Field, { inputStyle } from "../components/Field.jsx";

export default function Collaborations() {
  const [form, setForm] = useState({ nom: "", org: "", email: "", sujet: "", type: COLLAB_TYPES[0], description: "" });
  const [sent, setSent] = useState(false);
  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value });

  return (
    <div style={{ maxWidth: 900, margin: "0 auto", padding: "80px 24px 110px" }}>
      <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 20 }}>
        <NodeMark />
        <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 12, letterSpacing: "0.12em", textTransform: "uppercase", color: T.green }}>Collaborations</span>
      </div>
      <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(32px, 4.5vw, 46px)", fontWeight: 500, lineHeight: 1.12, margin: "0 0 16px" }}>Proposer une collaboration</h1>
      <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15.5, color: T.inkSoft, maxWidth: 560, marginBottom: 48 }}>
        Recherche, livre, article, intervention, partenariat, interview, étude ou simple message — chaque demande est lue personnellement.
      </p>

      {sent ? (
        <div style={{ padding: 32, border: `1px solid ${T.line}`, background: T.paperAlt, display: "flex", alignItems: "center", gap: 14 }}>
          <Check size={22} color={T.green} />
          <div>
            <div style={{ fontFamily: "'Newsreader', serif", fontSize: 19, fontWeight: 600 }}>Demande envoyée</div>
            <div style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft }}>Merci — une réponse suivra sous quelques jours.</div>
          </div>
        </div>
      ) : (
        <Reveal as="form" style={{ display: "flex", flexDirection: "column", gap: 22 }} onSubmit={(e) => { e.preventDefault(); setSent(true); }}>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 20 }} className="ytd-form-row">
            <Field label="Nom"><input required value={form.nom} onChange={set("nom")} style={inputStyle} /></Field>
            <Field label="Organisation"><input value={form.org} onChange={set("org")} style={inputStyle} /></Field>
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 20 }} className="ytd-form-row">
            <Field label="Email"><input required type="email" value={form.email} onChange={set("email")} style={inputStyle} /></Field>
            <Field label="Type de collaboration">
              <select value={form.type} onChange={set("type")} style={inputStyle}>
                {COLLAB_TYPES.map(t => <option key={t}>{t}</option>)}
              </select>
            </Field>
          </div>
          <Field label="Sujet"><input required value={form.sujet} onChange={set("sujet")} style={inputStyle} /></Field>
          <Field label="Description">
            <textarea required value={form.description} onChange={set("description")} rows={6} style={{ ...inputStyle, resize: "vertical", fontFamily: "'Inter', sans-serif" }} />
          </Field>
          <Field label="Pièces jointes">
            <div style={{ ...inputStyle, color: T.inkSoft, display: "flex", alignItems: "center", justifyContent: "space-between" }}>
              <span>Glisser un fichier ou cliquer pour parcourir</span><ImageIcon size={16} />
            </div>
          </Field>
          <div><Btn type="submit" variant="green">Envoyer la demande <ArrowRight size={15} /></Btn></div>
        </Reveal>
      )}
    </div>
  );
}
