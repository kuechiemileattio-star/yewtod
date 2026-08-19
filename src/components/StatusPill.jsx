import React from "react";
import { T } from "../theme.js";

export default function StatusPill({ statut }) {
  const colors = { "Publié": T.green, "Brouillon": T.red, "Nouveau": T.green, "En cours": T.red, "Archivé": T.inkSoft };
  const c = colors[statut] || T.inkSoft;
  return (
    <span className="ytd-status-pill" style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 10.5, color: c, border: `1px solid ${c}55`, background: `${c}12`, padding: "3px 9px", borderRadius: 20 }}>
      {statut}
    </span>
  );
}
