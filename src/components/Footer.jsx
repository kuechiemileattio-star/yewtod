import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { T } from "../theme.js";
import { PATHS, workPath } from "../lib/paths.js";
import { CONTENT_TYPES } from "../lib/contentTypes.js";
import { useSocialLinks } from "../hooks/useSiteSettings.js";
import { useNewsletterSubscribe } from "../hooks/useNewsletter.js";
import BrandLogo from "./BrandLogo.jsx";
import NodeMark from "./NodeMark.jsx";
import { ArrowUpRight, ArrowUp, Mail, Send, Check, MoveUpRight, Linkedin, Twitter, Youtube, Instagram, Facebook, Link2 } from "lucide-react";

const PLATFORM_ICONS = [
  [/linkedin/i, Linkedin],
  [/twitter|\bx\b/i, Twitter],
  [/youtube/i, Youtube],
  [/instagram/i, Instagram],
  [/facebook/i, Facebook],
];
function iconFor(platform) {
  return (PLATFORM_ICONS.find(([pattern]) => pattern.test(platform)) || [, Link2])[1];
}

const SITE_LINKS = [[PATHS.home, "Home"], [PATHS.works, "Works"], [PATHS.meet, "Meet Yewtod"], [PATHS.books, "Books"], [PATHS.collab, "Collaborations"]];

export default function Footer() {
  const navigate = useNavigate();
  const socialLinks = useSocialLinks();
  const { subscribe, submitting } = useNewsletterSubscribe();
  const [email, setEmail] = useState("");
  const [sent, setSent] = useState(false);
  const [showToTop, setShowToTop] = useState(false);

  useEffect(() => {
    const onScroll = () => setShowToTop(window.scrollY > 900);
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  async function handleSubscribe(e) {
    e.preventDefault();
    if (!email) return;
    try { await subscribe(email); setSent(true); } catch { /* ignored, hook tracks its own error */ }
  }

  return (
    <footer className="ytd-footer">
      <div className="ytd-footer-cta">
        <div className="ytd-footer-cta-inner" style={{ maxWidth: 1120, margin: "0 auto", padding: "64px 24px", display: "grid", gridTemplateColumns: "1fr auto", alignItems: "end", gap: 30 }}>
          <div><span className="ytd-footer-eyebrow">Une idée à partager ?</span><h2>Les bonnes questions méritent de bonnes conversations.</h2><p>Recherche, collaboration ou simple échange : ouvrez la discussion avec Yewtod SS.</p></div>
          <button onClick={() => navigate(PATHS.collab)} className="ytd-footer-cta-button"><Mail size={16} /> Démarrer une conversation <ArrowUpRight size={15} /></button>
        </div>
      </div>

      <div className="ytd-footer-base">
        <div className="ytd-footer-grid" style={{ maxWidth: 1120, margin: "0 auto", padding: "64px 24px 48px", display: "grid", gridTemplateColumns: "1.3fr 1fr 1fr 1.2fr", gap: 40 }}>
          <div className="ytd-footer-brand">
            <BrandLogo dark />
            <p style={{ fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 13.5, color: `${T.paper}AA`, lineHeight: 1.6, margin: "16px 0 20px", maxWidth: 300 }}>
              Un espace de réflexion sur les sciences sociales, les systèmes complexes, l'économie, la politique publique et l'intelligence artificielle.
            </p>
            <div className="ytd-footer-signature">
              <NodeMark color={T.lime} />
              <a href="mailto:contact@yewtod.ss" className="ytd-footer-mail">contact@yewtod.ss</a>
            </div>
          </div>

          <div>
            <div className="ytd-footer-heading">Explorer</div>
            {SITE_LINKS.map(([to, label]) => <button key={to} onClick={() => navigate(to)} className="ytd-footer-link">{label}<MoveUpRight size={12} /></button>)}
          </div>

          <div>
            <div className="ytd-footer-heading">Travaux</div>
            {CONTENT_TYPES.map(type => (
              <button key={type.table} onClick={() => navigate(`${PATHS.works}?categorie=${encodeURIComponent(type.label)}`)} className="ytd-footer-link">{type.label}<MoveUpRight size={12} /></button>
            ))}
          </div>

          <div>
            <div className="ytd-footer-heading">Rester informé</div>
            <p style={{ fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 12.5, color: `${T.paper}99`, margin: "0 0 14px", lineHeight: 1.5 }}>
              Un e-mail occasionnel quand un nouveau travail est publié.
            </p>
            {sent ? (
              <div className="ytd-footer-newsletter-sent"><Check size={14} /> Merci, à bientôt.</div>
            ) : (
              <form onSubmit={handleSubscribe} className="ytd-footer-newsletter-form">
                <input required type="email" value={email} onChange={e => setEmail(e.target.value)} placeholder="vous@exemple.com" />
                <button type="submit" aria-label="S'inscrire" disabled={submitting}><Send size={14} /></button>
              </form>
            )}
            {socialLinks.length > 0 && (
              <div className="ytd-footer-social-row">
                {socialLinks.map(link => {
                  const Icon = iconFor(link.platform);
                  return <a key={link.id} href={link.url} target="_blank" rel="noreferrer" aria-label={link.platform} title={link.platform}><Icon size={16} /></a>;
                })}
              </div>
            )}
          </div>
        </div>

        <div className="ytd-footer-marquee" aria-hidden="true">
          <span>SCIENCES SOCIALES&nbsp;&nbsp;/&nbsp;&nbsp;SYSTÈMES COMPLEXES&nbsp;&nbsp;/&nbsp;&nbsp;POLITIQUES PUBLIQUES&nbsp;&nbsp;/&nbsp;&nbsp;INTELLIGENCE ARTIFICIELLE&nbsp;&nbsp;/&nbsp;&nbsp;INNOVATION</span>
          <span>SCIENCES SOCIALES&nbsp;&nbsp;/&nbsp;&nbsp;SYSTÈMES COMPLEXES&nbsp;&nbsp;/&nbsp;&nbsp;POLITIQUES PUBLIQUES&nbsp;&nbsp;/&nbsp;&nbsp;INTELLIGENCE ARTIFICIELLE&nbsp;&nbsp;/&nbsp;&nbsp;INNOVATION</span>
        </div>

        <div className="ytd-footer-legal">
          <span>© 2026 Yewtod SS — Tous droits réservés</span>
          <button onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}>Retour en haut ↑</button>
        </div>
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
