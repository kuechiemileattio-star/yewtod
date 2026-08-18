import React from "react";
import { T } from "../theme.js";
import NodeMark from "./NodeMark.jsx";
import { ArrowUpRight } from "lucide-react";

export default function Footer({ setPage }) {
  const siteLinks = [["home", "Home"], ["works", "Works"], ["meet", "Meet Yewtod"], ["books", "Books"], ["collab", "Collaborations"]];
  return (
    <footer className="ytd-footer" style={{ borderTop: `1px solid ${T.line}`, marginTop: 100 }}>
      <div className="ytd-footer-cta" style={{ background: T.greenDeep, color: T.paper }}>
        <div style={{ maxWidth: 1120, margin: "0 auto", padding: "42px 24px", display: "flex", justifyContent: "space-between", alignItems: "center", gap: 24, flexWrap: "wrap" }}>
          <div><span style={{ fontFamily: "'Space Mono', monospace", fontSize: 10, color: T.lime, textTransform: "uppercase" }}>Une idée à partager ?</span><h2 style={{ fontFamily: "'DM Serif Display', serif", fontSize: 34, fontWeight: 400, margin: "8px 0 0" }}>Construisons quelque chose d'utile.</h2></div>
          <button onClick={() => setPage("collab")} className="ytd-footer-cta-button" style={{ border: "none", background: T.lime, color: T.ink, padding: "14px 18px", cursor: "pointer", fontFamily: "'Space Mono', monospace", fontSize: 11, display: "flex", alignItems: "center", gap: 8 }}>Parlons projet <ArrowUpRight size={15} /></button>
        </div>
      </div>
      <div style={{ background: T.paperAlt }}>
      <div style={{ maxWidth: 1120, margin: "0 auto", padding: "56px 24px 40px", display: "flex", flexWrap: "wrap", justifyContent: "space-between", gap: 40 }}>
        <div className="ytd-footer-brand" style={{ maxWidth: 320 }}>
          <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 14 }}>
            <NodeMark size={9} />
            <span style={{ fontFamily: "'DM Serif Display', serif", fontSize: 22 }}>Yewtod <i style={{ color: T.green }}>SS</i></span>
          </div>
          <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 13.5, color: T.inkSoft, lineHeight: 1.6 }}>
            Un espace de réflexion sur les sciences sociales, les systèmes complexes, l'économie, la politique publique et l'intelligence artificielle.
          </p>
        </div>
        <div style={{ display: "flex", gap: 60, flexWrap: "wrap" }}>
          <div>
            <div style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft, textTransform: "uppercase", letterSpacing: "0.08em", marginBottom: 12 }}>Site</div>
            {siteLinks.map(([key, label]) => <button key={key} onClick={() => setPage(key)} className="ytd-footer-link" style={{ fontFamily: "'Manrope', sans-serif", fontSize: 14, color: T.ink, marginBottom: 8, cursor: "pointer" }}>{label}</button>)}
          </div>
          <div>
            <div style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft, textTransform: "uppercase", letterSpacing: "0.08em", marginBottom: 12 }}>Suivre</div>
            {["Newsletter", "X / Twitter", "LinkedIn", "YouTube"].map(l => <button key={l} className="ytd-footer-link" style={{ fontFamily: "'Manrope', sans-serif", fontSize: 14, color: T.ink, marginBottom: 8, cursor: "pointer" }}>{l}</button>)}
          </div>
        </div>
      </div>
      </div>
      <div style={{ borderTop: `1px solid ${T.line}`, padding: "18px 24px", textAlign: "center", fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, color: T.inkSoft }}>
        © 2026 Yewtod SS — Tous droits réservés
      </div>
    </footer>
  );
}
