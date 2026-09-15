import React, { useState } from "react";
import { ArrowRight, Check, ArrowUpRight, FlaskConical, Mic2, PenLine } from "lucide-react";
import { T } from "../theme.js";
import { COLLAB_TYPES } from "../lib/contentTypes.js";
import { useCollaborationSubmit } from "../hooks/useCollaborations.js";
import useDocumentMeta from "../hooks/useDocumentMeta.js";
import NodeMark from "../components/NodeMark.jsx";
import Reveal from "../components/Reveal.jsx";
import Btn from "../components/Btn.jsx";
import Field, { inputStyle } from "../components/Field.jsx";
import DropzoneField from "../components/dashboard/DropzoneField.jsx";

export default function Collaborations() {
  useDocumentMeta("Collaborations", "Proposer une recherche, un article, une intervention ou un partenariat à Yewtod SS.");
  const [form, setForm] = useState({ nom: "", org: "", email: "", sujet: "", type: COLLAB_TYPES[0], description: "", attachment: "" });
  const [sent, setSent] = useState(false);
  const [uploading, setUploading] = useState(false);
  const { submit, submitting, error } = useCollaborationSubmit();
  const set = (k) => (e) => setForm({ ...form, [k]: e.target.value });

  async function handleSubmit(e) {
    e.preventDefault();
    if (uploading) return;
    try {
      await submit({
        name: form.nom,
        organization: form.org,
        email: form.email,
        subject: form.sujet,
        type: form.type,
        description: form.description,
        attachments: form.attachment ? [form.attachment] : [],
      });
      setSent(true);
    } catch {
      /* error surfaced below via hook state */
    }
  }

  return (
    <div className="ytd-collab-page" style={{ maxWidth: 1120, margin: "0 auto", padding: "72px 24px 110px" }}>
      <div className="ytd-collab-hero">
      <div>
      <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 20 }}>
        <NodeMark />
        <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 12, letterSpacing: "0.12em", textTransform: "uppercase", color: T.green }}>Collaborations</span>
      </div>
      <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(38px, 5vw, 62px)", fontWeight: 500, lineHeight: 1.02, margin: "0 0 16px" }}>Vous avez une idée ? Écrivez-moi.</h1>
      <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15.5, color: T.inkSoft, maxWidth: 560, marginBottom: 48 }}>
        Ça peut être une recherche à moitié formulée, un article que vous voulez co-écrire, ou juste une question qui vous trotte dans la tête depuis un moment. Je réponds à tout le monde, même quand ce n'est pas encore très clair.
      </p>
      </div>
      <div className="ytd-collab-hero-note"><ArrowUpRight size={20} /><span>Je lis chaque message moi-même.</span></div>
      </div>

      <div className="ytd-collab-layout">
      <aside className="ytd-collab-aside">
        <span className="ytd-collab-aside-label">Ce qu'on peut faire ensemble</span>
        {[[FlaskConical, "De la recherche", "Vous avez un terrain, des données ou juste une intuition à tester ? On regarde ensemble si ça tient."], [PenLine, "De l'écriture", "Un article co-signé, une note, un chapitre de livre — au format qui vous convient."], [Mic2, "Une prise de parole", "Un entretien, une intervention, une conversation enregistrée ou non."]].map(([Icon, title, text], index) => (
          <Reveal key={title} delay={index * 90}>
            <div className="ytd-collab-type"><Icon size={18} /><div><strong>{title}</strong><span>{text}</span></div></div>
          </Reveal>
        ))}
      </aside>
      <div className="ytd-collab-form-wrap">

      {sent ? (
        <div className="ytd-collab-success" style={{ padding: 32, border: `1px solid ${T.line}`, background: T.paperAlt, display: "flex", alignItems: "center", gap: 14 }}>
          <Check size={22} color={T.green} />
          <div>
            <div style={{ fontFamily: "'Newsreader', serif", fontSize: 19, fontWeight: 600 }}>Demande envoyée</div>
            <div style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft }}>Merci — une réponse suivra sous quelques jours.</div>
          </div>
        </div>
      ) : (
        <Reveal as="form" className="ytd-collab-form" style={{ display: "flex", flexDirection: "column", gap: 22 }} onSubmit={handleSubmit}>
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
          <Field label="Pièce jointe (facultatif)" hint="PDF, document ou image — un CV, un projet détaillé, un exemple de travail...">
            <DropzoneField
              kind="file" bucket="collaboration-attachments" accept=".pdf,.doc,.docx,image/*"
              value={form.attachment} onChange={url => setForm({ ...form, attachment: url })}
              onUploadingChange={setUploading}
            />
          </Field>
          {error && <p style={{ color: T.red, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>Une erreur est survenue, merci de réessayer.</p>}
          <div><Btn type="submit" variant="green" disabled={uploading} style={{ opacity: submitting || uploading ? 0.7 : 1, cursor: uploading ? "wait" : "pointer" }}>{uploading ? "Envoi du fichier…" : <>Envoyer la demande <ArrowRight size={15} /></>}</Btn></div>
        </Reveal>
      )}
      </div>
      </div>
    </div>
  );
}
