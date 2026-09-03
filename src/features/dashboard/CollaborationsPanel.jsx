import React, { useState } from "react";
import { Filter, Archive } from "lucide-react";
import { T } from "../../theme.js";
import { useAdminCollaborations } from "../../hooks/useAdminCollaborations.js";
import { fmtDate } from "../../lib/contentTypes.js";
import Btn from "../../components/Btn.jsx";
import Field, { inputStyle } from "../../components/Field.jsx";
import StatusPill from "../../components/StatusPill.jsx";

export default function CollaborationsPanel() {
  const { collabs, loading, updateStatus } = useAdminCollaborations();
  const [filter, setFilter] = useState("Tous");
  const [selected, setSelected] = useState(null);

  const filtered = filter === "Tous" ? collabs : collabs.filter(c => c.statut === filter);

  function exportCollabs() {
    const csv = ["Nom;Organisation;Email;Type;Statut;Date", ...collabs.map(c => [c.nom, c.org, c.email || "", c.type, c.statut, c.date].join(";"))].join("\n");
    const link = document.createElement("a");
    link.href = URL.createObjectURL(new Blob([csv], { type: "text/csv;charset=utf-8" }));
    link.download = "collaborations-yewtod.csv";
    link.click();
    URL.revokeObjectURL(link.href);
  }

  return (
    <div className="ytd-admin-view">
      <h1 style={{ fontFamily: "'Fraunces', serif", fontSize: 30, fontWeight: 500, margin: "0 0 6px" }}>Collaborations</h1>
      <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14, color: T.inkSoft, marginBottom: 24 }}>Demandes reçues via le formulaire public, en direct depuis la base.</p>
      <div style={{ display: "flex", gap: 8, marginBottom: 22, alignItems: "center", flexWrap: "wrap" }}>
        <Filter size={14} color={T.inkSoft} />
        {["Tous", "Nouveau", "En cours", "Archivé"].map(s => (
          <button key={s} onClick={() => setFilter(s)} style={{
            fontFamily: "'IBM Plex Mono', monospace", fontSize: 12, padding: "6px 12px", cursor: "pointer", borderRadius: 20,
            border: `1px solid ${filter === s ? T.ink : T.line}`, background: filter === s ? T.ink : "transparent",
            color: filter === s ? T.paper : T.inkSoft,
          }}>{s}</button>
        ))}
        <span style={{ flex: 1 }} />
        <Btn variant="outline" onClick={exportCollabs} style={{ fontSize: 12.5, padding: "8px 14px" }}><Archive size={13} /> Exporter</Btn>
      </div>

      {loading ? <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</p> : (
        <div style={{ overflowX: "auto" }}>
          <table style={{ width: "100%", minWidth: 640, borderCollapse: "collapse", fontFamily: "'Inter', sans-serif", fontSize: 14 }}>
            <thead>
              <tr style={{ borderBottom: `1px solid ${T.line}`, textAlign: "left" }}>
                {["Nom", "Organisation", "Type", "Date", "Statut"].map(h => (
                  <th key={h} style={{ padding: "10px 8px", fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft, textTransform: "uppercase", fontWeight: 500 }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {filtered.map(c => (
                <tr key={c.id} className="ytd-table-row" style={{ borderBottom: `1px solid ${T.line}` }}>
                  <td style={{ padding: "12px 8px" }}><button onClick={() => setSelected(c)} style={{ border: 0, padding: 0, background: "none", color: T.ink, cursor: "pointer", font: "inherit", textDecoration: "underline" }}>{c.nom}</button></td>
                  <td style={{ padding: "12px 8px", color: T.inkSoft }}>{c.org}</td>
                  <td style={{ padding: "12px 8px", color: T.inkSoft }}>{c.type}</td>
                  <td style={{ padding: "12px 8px", color: T.inkSoft, fontFamily: "'IBM Plex Mono', monospace", fontSize: 12.5 }}>{fmtDate(c.date)}</td>
                  <td style={{ padding: "12px 8px" }}><StatusPill statut={c.statut} /></td>
                </tr>
              ))}
              {filtered.length === 0 && <tr><td colSpan={5} style={{ padding: "18px 8px", color: T.inkSoft }}>Aucune demande dans ce filtre.</td></tr>}
            </tbody>
          </table>
        </div>
      )}

      {selected && (
        <div className="ytd-admin-panel" style={{ marginTop: 24, padding: 22, border: `1px solid ${T.green}`, background: T.paper }}>
          <div style={{ display: "flex", justifyContent: "space-between", gap: 16, alignItems: "start" }}>
            <div>
              <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 10, color: T.green, textTransform: "uppercase" }}>Fiche de collaboration</span>
              <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 24, margin: "7px 0 4px" }}>{selected.nom}</h2>
              <p style={{ margin: 0, color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>{selected.org} · {selected.type} · {fmtDate(selected.date)} {selected.email ? `· ${selected.email}` : ""}</p>
            </div>
            <button onClick={() => setSelected(null)} style={{ border: 0, background: "none", cursor: "pointer", color: T.inkSoft }}>Fermer</button>
          </div>
          <p style={{ fontFamily: "'Fraunces', serif", fontSize: 18, lineHeight: 1.55, marginBottom: 18 }}>{selected.description || "Aucune description fournie."}</p>
          <Field label="Statut">
            <select value={selected.statut} onChange={async e => { await updateStatus(selected.id, e.target.value); setSelected({ ...selected, statut: e.target.value }); }} style={inputStyle}>
              <option>Nouveau</option><option>En cours</option><option>Archivé</option>
            </select>
          </Field>
        </div>
      )}
    </div>
  );
}
