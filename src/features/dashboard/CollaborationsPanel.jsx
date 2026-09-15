import React, { useState } from "react";
import { Filter, Archive, Paperclip, Inbox, Clock, CheckCircle2 } from "lucide-react";
import { T } from "../../theme.js";
import { useAdminCollaborations } from "../../hooks/useAdminCollaborations.js";
import { fmtDate } from "../../lib/contentTypes.js";
import Btn from "../../components/Btn.jsx";
import Field, { inputStyle } from "../../components/Field.jsx";
import StatusPill from "../../components/StatusPill.jsx";

const STAT_TILES = [
  { statut: "Nouveau", label: "Nouvelles demandes", icon: Inbox },
  { statut: "En cours", label: "En cours de traitement", icon: Clock },
  { statut: "Archivé", label: "Archivées", icon: CheckCircle2 },
];

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
    <div className="ytd-dashboard-new ytd-admin-view">
      <div className="ytd-admin-section-heading">
        <div><span className="ytd-admin-kicker">Organisation</span><h1>Collaborations</h1><p>Demandes reçues via le formulaire public, en direct depuis la base.</p></div>
      </div>

      <div className="ytd-dashboard-stat-grid" style={{ gridTemplateColumns: "repeat(3, minmax(0, 1fr))" }}>
        {STAT_TILES.map(({ statut, label, icon: Icon }, index) => (
          <article key={statut} className="ytd-dashboard-stat" style={{ animationDelay: `${index * 70}ms` }}>
            <div className="ytd-dashboard-stat-top"><Icon size={18} color={T.green} /></div>
            <strong>{collabs.filter(c => c.statut === statut).length}</strong>
            <small>{label}</small>
          </article>
        ))}
      </div>

      <div>
        <div style={{ display: "flex", gap: 8, marginBottom: 18, alignItems: "center", flexWrap: "wrap" }}>
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
          <div className="ytd-admin-table-scroll">
            <table className="ytd-admin-table" style={{ minWidth: 640 }}>
              <thead>
                <tr>{["Nom", "Organisation", "Type", "Date", "Statut"].map(h => <th key={h}>{h}</th>)}</tr>
              </thead>
              <tbody>
                {filtered.map(c => (
                  <tr key={c.id}>
                    <td style={{ whiteSpace: "normal" }}>
                      <button onClick={() => setSelected(c)} style={{ display: "inline-flex", alignItems: "center", gap: 6, border: 0, padding: 0, background: "none", color: T.ink, cursor: "pointer", font: "inherit", textDecoration: "underline" }}>
                        {c.nom} {c.attachments.length > 0 && <Paperclip size={12} color={T.inkSoft} />}
                      </button>
                    </td>
                    <td style={{ color: T.inkSoft }}>{c.org}</td>
                    <td style={{ color: T.inkSoft }}>{c.type}</td>
                    <td style={{ color: T.inkSoft, fontFamily: "'IBM Plex Mono', monospace", fontSize: 12.5 }}>{fmtDate(c.date)}</td>
                    <td><StatusPill statut={c.statut} /></td>
                  </tr>
                ))}
                {filtered.length === 0 && <tr><td colSpan={5} style={{ color: T.inkSoft }}>Aucune demande dans ce filtre.</td></tr>}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {selected && (
        <div style={{ padding: 22, border: `1px solid ${T.green}`, borderRadius: 12, background: T.paper }}>
          <div style={{ display: "flex", justifyContent: "space-between", gap: 16, alignItems: "start" }}>
            <div>
              <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 10, color: T.green, textTransform: "uppercase" }}>Fiche de collaboration</span>
              <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 24, margin: "7px 0 4px" }}>{selected.nom}</h2>
              <p style={{ margin: 0, color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13 }}>{selected.org} · {selected.type} · {fmtDate(selected.date)} {selected.email ? `· ${selected.email}` : ""}</p>
            </div>
            <button onClick={() => setSelected(null)} style={{ border: 0, background: "none", cursor: "pointer", color: T.inkSoft }}>Fermer</button>
          </div>
          <p style={{ fontFamily: "'Fraunces', serif", fontSize: 18, lineHeight: 1.55, marginBottom: 18, marginTop: 18 }}>{selected.description || "Aucune description fournie."}</p>
          {selected.attachments?.length > 0 && (
            <div style={{ marginBottom: 18 }}>
              {selected.attachments.map(url => (
                <a key={url} href={url} target="_blank" rel="noreferrer" style={{ display: "inline-flex", alignItems: "center", gap: 6, marginRight: 10, padding: "8px 12px", border: `1px solid ${T.line}`, borderRadius: 6, background: T.paperAlt, color: T.ink, textDecoration: "none", fontFamily: "'Inter', sans-serif", fontSize: 13 }}>
                  <Paperclip size={13} color={T.green} /> Pièce jointe
                </a>
              ))}
            </div>
          )}
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
