import React from "react";
import { ArrowLeft, Calendar, Building2, Mail } from "lucide-react";
import { T } from "../theme.js";
import { fmtDate } from "../data.js";
import Tag from "../components/Tag.jsx";

export default function CollaborationDetail({ collab, back }) {
  if (!collab) return null;
  return (
    <div style={{ maxWidth: 760, margin: "0 auto", padding: "56px 24px 110px" }}>
      <button onClick={back} style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 8, fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 36 }}>
        <ArrowLeft size={15} /> Retour aux collaborations
      </button>
      <Tag>{collab.statut}</Tag>
      <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(34px, 5vw, 52px)", fontWeight: 500, lineHeight: 1.1, margin: "16px 0 10px" }}>{collab.nom}</h1>
      <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 16, color: T.inkSoft, marginBottom: 34 }}>{collab.type}</p>
      <div style={{ display: "grid", gap: 14, padding: 22, border: `1px solid ${T.line}`, background: T.paper }}>
        <div style={{ display: "flex", alignItems: "center", gap: 10, fontFamily: "'Inter', sans-serif", fontSize: 14 }}><Building2 size={16} color={T.green} /> {collab.org}</div>
        <div style={{ display: "flex", alignItems: "center", gap: 10, fontFamily: "'Inter', sans-serif", fontSize: 14 }}><Calendar size={16} color={T.green} /> Demande reçue le {fmtDate(collab.date)}</div>
        {collab.email && <div style={{ display: "flex", alignItems: "center", gap: 10, fontFamily: "'Inter', sans-serif", fontSize: 14 }}><Mail size={16} color={T.green} /> {collab.email}</div>}
      </div>
      <div style={{ marginTop: 36, fontFamily: "'Newsreader', serif", fontSize: 19, lineHeight: 1.7 }}>
        <p>Cette fiche rassemble les informations de la demande afin de pouvoir la consulter, la qualifier et suivre sa progression depuis l'espace d'administration.</p>
        <p>Le détail du projet et les prochaines étapes pourront être ajoutés ici au fur et à mesure du traitement.</p>
      </div>
    </div>
  );
}
