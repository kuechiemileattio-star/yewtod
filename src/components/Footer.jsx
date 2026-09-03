import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { T } from "../theme.js";
import { PATHS } from "../lib/paths.js";
import { useSocialLinks } from "../hooks/useSiteSettings.js";
import BrandLogo from "./BrandLogo.jsx";
import { ArrowUpRight, ArrowUp, Mail, MoveUpRight, Linkedin, Twitter, Youtube, Instagram, Facebook, Link2 } from "lucide-react";

const PLATFORM_ICONS = [
  [/linkedin/i, Linkedin],
  [/twitter|\bx\b/i, Twitter],
  [/youtube/i, Youtube],
  [/instagram/i, Instagram],
  [/facebook/i, Facebook],
];

function iconFor(platform) {
  const match = PLATFORM_ICONS.find(([pattern]) => pattern.test(platform));
  return match ? match[1] : Link2;
}

export default function Footer() {
  const navigate = useNavigate();
  const socialLinks = useSocialLinks();
  const [showToTop, setShowToTop] = useState(false);
  const siteLinks = [[PATHS.home, "Home"], [PATHS.works, "Works"], [PATHS.meet, "Meet Yewtod"], [PATHS.books, "Books"], [PATHS.collab, "Collaborations"]];

  useEffect(() => {
    const onScroll = () => setShowToTop(window.scrollY > 900);
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  function goToNewsletter() {
    navigate(`${PATHS.home}#newsletter`);
  }

  return (
    <footer className="ytd-footer">
      <div className="ytd-footer-cta">
        <div className="ytd-footer-cta-inner" style={{ maxWidth: 1120, margin: "0 auto", padding: "64px 24px", display: "grid", gridTemplateColumns: "1fr auto", alignItems: "end", gap: 30 }}>
          <div><span className="ytd-footer-eyebrow">Une idée à partager ?</span><h2>Les bonnes questions méritent de bonnes conversations.</h2><p>Recherche, collaboration ou simple échange : ouvrez la discussion avec Yewtod SS.</p></div>
          <button onClick={() => navigate(PATHS.collab)} className="ytd-footer-cta-button"><Mail size={16} /> Démarrer une conversation <ArrowUpRight size={15} /></button>
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
        <div><div className="ytd-footer-heading">Explorer</div>{siteLinks.map(([to, label]) => <button key={to} onClick={() => navigate(to)} className="ytd-footer-link">{label}<MoveUpRight size={12} /></button>)}</div>
        <div>
          <div className="ytd-footer-heading">Suivre</div>
          <button onClick={goToNewsletter} className="ytd-footer-link"><Mail size={13} style={{ marginRight: 2 }} />Newsletter<MoveUpRight size={12} /></button>
          {socialLinks.map(link => {
            const Icon = iconFor(link.platform);
            return (
              <a key={link.id} href={link.url} target="_blank" rel="noreferrer" className="ytd-footer-link">
                <Icon size={13} style={{ marginRight: 2 }} />{link.platform}<MoveUpRight size={12} />
              </a>
            );
          })}
        </div>
      </div>
      </div>
      <div className="ytd-footer-legal" style={{ borderTop: `1px solid ${T.paper}22`, padding: "18px 24px", margin: 0, background: T.ink, textAlign: "center", fontFamily: "'JetBrains Mono', monospace", fontSize: 11.5, color: `${T.paper}88` }}>
        © 2026 Yewtod SS — Tous droits réservés
      </div>
      <button
        onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
        aria-label="Retour en haut de page"
        className="ytd-to-top"
        style={{ opacity: showToTop ? 1 : 0, pointerEvents: showToTop ? "auto" : "none" }}
      >
        <ArrowUp size={17} />
      </button>
    </footer>
  );
}
