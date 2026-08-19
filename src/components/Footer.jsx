import React from "react";
import { T } from "../theme.js";
import NodeMark from "./NodeMark.jsx";
import BrandLogo from "./BrandLogo.jsx";
import { ArrowUpRight, Mail, MoveUpRight } from "lucide-react";

export default function Footer({ setPage }) {
  const siteLinks = [["home", "Home"], ["works", "Works"], ["meet", "Meet Yewtod"], ["books", "Books"], ["collab", "Collaborations"]];
  return (
    <footer className="ytd-footer">
      <div className="ytd-footer-cta">
        <div className="ytd-footer-cta-inner" style={{ maxWidth: 1120, margin: "0 auto", padding: "64px 24px", display: "grid", gridTemplateColumns: "1fr auto", alignItems: "end", gap: 30 }}>
          <div><span className="ytd-footer-eyebrow">Une idée à partager ?</span><h2>Les bonnes questions méritent de bonnes conversations.</h2><p>Recherche, collaboration ou simple échange : ouvrez la discussion avec Yewtod SS.</p></div>
          <button onClick={() => setPage("collab")} className="ytd-footer-cta-button"><Mail size={16} /> Démarrer une conversation <ArrowUpRight size={15} /></button>
        </div>
      </div>
      <div className="ytd-footer-marquee" aria-hidden="true"><span>SCIENCES SOCIALES&nbsp;&nbsp; / &nbsp;&nbsp;SYSTÈMES COMPLEXES&nbsp;&nbsp; / &nbsp;&nbsp;POLITIQUES PUBLIQUES&nbsp;&nbsp; / &nbsp;&nbsp;IA</span><span>SCIENCES SOCIALES&nbsp;&nbsp; / &nbsp;&nbsp;SYSTÈMES COMPLEXES&nbsp;&nbsp; / &nbsp;&nbsp;POLITIQUES PUBLIQUES&nbsp;&nbsp; / &nbsp;&nbsp;IA</span></div>
      <div className="ytd-footer-base">
      <div style={{ maxWidth: 1120, margin: "0 auto", padding: "48px 24px 38px", display: "grid", gridTemplateColumns: "1.4fr 1fr 1fr", gap: 56 }}>
        <div className="ytd-footer-brand" style={{ maxWidth: 320 }}>
          <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 14 }}>
            <BrandLogo dark />
          </div>
          <p style={{ fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 13.5, color: `${T.paper}AA`, lineHeight: 1.6 }}>
            Un espace de réflexion sur les sciences sociales, les systèmes complexes, l'économie, la politique publique et l'intelligence artificielle.
          </p>
        </div>
        <div><div className="ytd-footer-heading">Explorer</div>{siteLinks.map(([key, label]) => <button key={key} onClick={() => setPage(key)} className="ytd-footer-link">{label}<MoveUpRight size={12} /></button>)}</div>
        <div><div className="ytd-footer-heading">Suivre</div>{["Newsletter", "X / Twitter", "LinkedIn", "YouTube"].map(l => <button key={l} className="ytd-footer-link">{l}<MoveUpRight size={12} /></button>)}</div>
      </div>
      </div>
      <div className="ytd-footer-legal" style={{ borderTop: `1px solid ${T.paper}22`, padding: "18px 24px", margin: 0, background: T.ink, textAlign: "center", fontFamily: "'JetBrains Mono', monospace", fontSize: 11.5, color: `${T.paper}88` }}>
        © 2026 Yewtod SS — Tous droits réservés
      </div>
    </footer>
  );
}
