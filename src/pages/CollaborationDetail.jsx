import React from "react";
import { ArrowLeft, Calendar, Building2, Mail } from "lucide-react";
import { T } from "../theme.js";
import { fmtDate } from "../data.js";
import Tag from "../components/Tag.jsx";

export default function CollaborationDetail({ collab, back }) {
  if (!collab) return null;
  return (
    <div className="ytd-collab-detail-page" style={{ maxWidth: 980, margin: "0 auto", padding: "56px 24px 110px" }}>
      <button onClick={back} style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 8, fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 36 }}>
        <ArrowLeft size={15} /> Retour aux collaborations
      </button>
      <div className="ytd-collab-detail-hero"><div><Tag>{collab.statut}</Tag><h1>{collab.nom}</h1><p>{collab.type}</p></div><div className="ytd-collab-detail-mark">Demande<br />de collaboration</div></div>
      <div className="ytd-collab-detail-info">
        <div><Building2 size={18} color={T.green} /><span>Organisation<strong>{collab.org}</strong></span></div>
        <div><Calendar size={18} color={T.green} /><span>Reçue le<strong>{fmtDate(collab.date)}</strong></span></div>
        {collab.email && <div><Mail size={18} color={T.green} /><span>Contact<strong>{collab.email}</strong></span></div>}
      </div>
      <div className="ytd-collab-detail-copy">
        <p>Cette fiche rassemble les informations de la demande afin de pouvoir la consulter, la qualifier et suivre sa progression depuis l'espace d'administration.</p>
        <p>Le détail du projet et les prochaines étapes pourront être ajoutés ici au fur et à mesure du traitement.</p>
      </div>
    </div>
  );
}
