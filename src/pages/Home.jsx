import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import { ArrowRight, Mail, Check, Compass, Network, Lightbulb } from "lucide-react";
import { T } from "../theme.js";
import { fmtDate } from "../lib/contentTypes.js";
import { PATHS, workPath } from "../lib/paths.js";
import { useWorks } from "../hooks/useWorks.js";
import { useSiteSettings } from "../hooks/useSiteSettings.js";
import { useNewsletterSubscribe } from "../hooks/useNewsletter.js";
import useDocumentMeta from "../hooks/useDocumentMeta.js";
import NodeMark from "../components/NodeMark.jsx";
import Divider from "../components/Divider.jsx";
import Reveal from "../components/Reveal.jsx";
import Cover from "../components/Cover.jsx";
import Tag from "../components/Tag.jsx";
import SectionLabel from "../components/SectionLabel.jsx";
import Btn from "../components/Btn.jsx";

export default function Home() {
  const navigate = useNavigate();
  const { works, loading } = useWorks();
  const { settings } = useSiteSettings();
  const { subscribe, submitting } = useNewsletterSubscribe();
  const [email, setEmail] = useState("");
  const [sent, setSent] = useState(false);
  useDocumentMeta(null, settings.seo_description || "Yewtod SS, média personnel de réflexion sur les sciences sociales, les systèmes complexes, l'économie, la politique publique et l'intelligence artificielle.");

  const openWork = work => navigate(workPath(work.routeSlug, work.slug));
  const latest = works[0];
  const report = works.find(w => w.category === "Rapports");
  const video = works.find(w => w.category === "Séries documentaires");
  const featured = works.slice(1, 5);
  const reflection = settings.reflection_of_week;

  async function handleSubscribe(e) {
    e.preventDefault();
    if (!email) return;
    try { await subscribe(email); setSent(true); } catch { /* surfaced via hook error state */ }
  }

  if (loading) {
    return <div style={{ maxWidth: 1120, margin: "0 auto", padding: "120px 24px", color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</div>;
  }

  if (!latest) {
    return (
      <div style={{ maxWidth: 1120, margin: "0 auto", padding: "120px 24px 100px" }}>
        <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(34px, 4.5vw, 48px)", fontWeight: 500 }}>Des idées claires pour comprendre un monde complexe.</h1>
        <p style={{ fontFamily: "'Inter', sans-serif", color: T.inkSoft, fontSize: 16, maxWidth: 560 }}>Les premières publications de Yewtod SS arrivent bientôt.</p>
      </div>
    );
  }

  return (
    <div className="ytd-home-page">
      {/* HERO */}
      <section style={{ maxWidth: 1120, margin: "0 auto", padding: "72px 24px 60px", display: "grid", gridTemplateColumns: "1.05fr .95fr", alignItems: "center", gap: 56 }} className="ytd-home-hero ytd-editorial-hero">
        <div className="ytd-stagger" style={{ maxWidth: 720 }}>
          <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 22 }}>
            <NodeMark pulse />
            <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 12, letterSpacing: "0.12em", textTransform: "uppercase", color: T.green }}>Média de recherche appliquée</span>
          </div>
          <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(38px, 5.5vw, 62px)", lineHeight: 1.06, fontWeight: 500, color: T.ink, letterSpacing: "-0.015em", margin: 0 }}>
            Des idées claires pour comprendre un monde complexe.
          </h1>
          <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 18, lineHeight: 1.65, color: T.inkSoft, marginTop: 24, maxWidth: 620 }}>
            Yewtod SS est une plateforme indépendante qui rend accessibles les sciences sociales, l'économie, les politiques publiques, les systèmes complexes et l'intelligence artificielle. Nous transformons la recherche en repères utiles pour mieux comprendre, décider et agir.
          </p>
          <div style={{ display: "flex", gap: 14, marginTop: 32, flexWrap: "wrap" }}>
            <Btn variant="green" onClick={() => navigate(PATHS.works)}>Explorer les travaux <ArrowRight size={16} /></Btn>
            <Btn variant="outline" onClick={() => navigate(PATHS.meet)}>Découvrir le projet</Btn>
          </div>
        </div>
        <div className="ytd-hero-visual" style={{ position: "relative", minHeight: 360 }}>
          <div style={{ position: "absolute", inset: "12px 0 0 36px", background: T.greenDeep, transform: "rotate(3deg)" }} />
          <div onClick={() => openWork(latest)} role="button" tabIndex={0} onKeyDown={e => e.key === "Enter" && openWork(latest)} style={{ position: "relative", background: T.paperAlt, padding: 12, border: `1px solid ${T.line}`, transform: "rotate(-3deg)", cursor: "pointer" }}>
            <Cover tone={latest.tone} label={latest.category} image={latest.coverImage} tall />
            <div style={{ padding: "14px 8px 4px" }}>
              <span style={{ fontFamily: "'Space Mono', monospace", fontSize: 10, color: T.green, textTransform: "uppercase" }}>À lire maintenant</span>
              <p style={{ fontFamily: "'DM Serif Display', serif", fontSize: 22, lineHeight: 1.15, margin: "7px 0 0", color: T.ink }}>{latest.title}</p>
            </div>
          </div>
        </div>
      </section>

      <section className="ytd-platform-intro" style={{ maxWidth: 1120, margin: "0 auto", padding: "10px 24px 72px" }}>
        <div className="ytd-platform-intro-heading">
          <span>Ce que vous trouverez ici</span>
          <h2>Une plateforme pour prendre du recul, puis passer à l'action.</h2>
        </div>
        <div className="ytd-platform-pillars">
          {[
            [Compass, "Comprendre", "Des articles et des analyses pour décoder les forces qui transforment nos sociétés."],
            [Network, "Relier", "Des ponts entre données, institutions, économie, technologie et expériences de terrain."],
            [Lightbulb, "Expérimenter", "Des méthodes, des livres et des idées testées pour apprendre sans simplifier à outrance."],
          ].map(([Icon, title, text], index) => (
            <Reveal key={title} delay={index * 100}>
              <article className="ytd-platform-pillar">
                <div className="ytd-platform-icon"><Icon size={20} /></div>
                <h3>{title}</h3>
                <p>{text}</p>
              </article>
            </Reveal>
          ))}
        </div>
      </section>

      <Divider margin="0 0 64px" />

      {/* DERNIER ARTICLE / RAPPORT / VIDÉO */}
      <Reveal as="section" className="ytd-editorial-section" style={{ maxWidth: 1120, margin: "0 auto", padding: "0 24px 72px" }}>
        <SectionLabel>À la une</SectionLabel>
        <div style={{ display: "grid", gridTemplateColumns: "1.3fr 1fr", gap: 48 }} className="ytd-hero-grid">
          <div onClick={() => openWork(latest)} className="ytd-card" style={{ cursor: "pointer" }}>
            <Cover tone={latest.tone} label={latest.category} image={latest.coverImage} tall />
            <div style={{ marginTop: 18 }}>
              <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 12, color: T.inkSoft }}>{fmtDate(latest.date)}{latest.readTime ? ` · ${latest.readTime}` : ""}</span>
              <h2 style={{ fontFamily: "'Newsreader', serif", fontSize: 30, fontWeight: 500, margin: "10px 0 10px", lineHeight: 1.2 }}>{latest.title}</h2>
              <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15.5, color: T.inkSoft, lineHeight: 1.6 }}>{latest.excerpt}</p>
            </div>
          </div>

          <div style={{ display: "flex", flexDirection: "column", gap: 32 }}>
            {[["Dernier rapport", report], ["Dernière vidéo", video]].filter(([, item]) => item).map(([label, item]) => (
              <div key={label} onClick={() => openWork(item)} className="ytd-card" style={{ cursor: "pointer", display: "flex", gap: 16 }}>
                <div style={{ width: 120, flexShrink: 0 }}><Cover tone={item.tone} label={item.category} image={item.coverImage} /></div>
                <div>
                  <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 10.5, color: T.green, textTransform: "uppercase", letterSpacing: "0.06em" }}>{label}</span>
                  <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 18, fontWeight: 600, margin: "6px 0 6px", lineHeight: 1.25 }}>{item.title}</h3>
                  <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, color: T.inkSoft }}>{fmtDate(item.date)}</span>
                </div>
              </div>
            ))}

            {/* Réflexion de la semaine — éditable depuis le dashboard (Paramètres) */}
            {reflection?.text && (
              <div style={{ background: T.paperAlt, border: `1px solid ${T.line}`, padding: 24, borderLeft: `3px solid ${T.red}` }}>
                <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 10.5, color: T.red, textTransform: "uppercase", letterSpacing: "0.06em" }}>Réflexion de la semaine</span>
                <p style={{ fontFamily: "'Newsreader', serif", fontStyle: "italic", fontSize: 18, lineHeight: 1.5, margin: "10px 0 0" }}>
                  « {reflection.text} »
                </p>
                {reflection.author && <span style={{ display: "block", marginTop: 10, fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft }}>— {reflection.author}</span>}
              </div>
            )}
          </div>
        </div>
      </Reveal>

      {/* TRAVAUX MIS EN AVANT */}
      {featured.length > 0 && (
        <Reveal as="section" className="ytd-editorial-section ytd-editorial-section-tinted" style={{ maxWidth: 1120, margin: "0 auto", padding: "0 24px 72px" }}>
          <SectionLabel>Travaux mis en avant</SectionLabel>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 28 }} className="ytd-grid-4">
            {featured.map((w, i) => (
              <div key={w.id} onClick={() => openWork(w)} className="ytd-card" style={{ cursor: "pointer", transitionDelay: `${i * 60}ms` }}>
                <Cover tone={w.tone} label={w.category} image={w.coverImage} />
                <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 17, fontWeight: 600, margin: "14px 0 8px", lineHeight: 1.28 }}>{w.title}</h3>
                <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft }}>{fmtDate(w.date)}{w.readTime ? ` · ${w.readTime}` : ""}</span>
              </div>
            ))}
          </div>
        </Reveal>
      )}

      <Divider margin="0 0 72px" />

      {/* DERNIÈRES PUBLICATIONS */}
      <Reveal as="section" className="ytd-editorial-section" style={{ maxWidth: 1120, margin: "0 auto", padding: "0 24px 72px" }}>
        <SectionLabel>Dernières publications</SectionLabel>
        <div>
          {works.slice(0, 6).map((w, i) => (
            <div key={w.id} onClick={() => openWork(w)} className="ytd-row" style={{
              cursor: "pointer", display: "flex", justifyContent: "space-between", alignItems: "center",
              padding: "20px 0", borderTop: i === 0 ? `1px solid ${T.line}` : "none", borderBottom: `1px solid ${T.line}`, gap: 20,
            }}>
              <div style={{ display: "flex", alignItems: "center", gap: 20, minWidth: 0 }}>
                <Tag>{w.category}</Tag>
                <h4 style={{ fontFamily: "'Newsreader', serif", fontSize: 18, fontWeight: 500, margin: 0, whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>{w.title}</h4>
              </div>
              <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 12, color: T.inkSoft, flexShrink: 0 }}>{fmtDate(w.date)}</span>
            </div>
          ))}
        </div>
      </Reveal>

      {/* NEWSLETTER */}
      <Reveal as="section" id="newsletter" className="ytd-editorial-section" style={{ maxWidth: 1120, margin: "0 auto", padding: "0 24px 20px", scrollMarginTop: 100 }}>
        <div className="ytd-newsletter-panel" style={{ background: T.paper, borderLeft: `1px solid ${T.line}`, borderRight: `1px solid ${T.line}`, borderBottom: `1px solid ${T.line}`, borderTop: `4px solid ${T.green}`, padding: "48px 40px", display: "flex", justifyContent: "space-between", alignItems: "center", flexWrap: "wrap", gap: 28 }}>
          <div style={{ maxWidth: 460 }}>
            <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 10.5, color: T.green, textTransform: "uppercase", letterSpacing: "0.08em" }}>Rester informé·e</span>
            <h3 style={{ fontFamily: "'Fraunces', serif", fontSize: 26, color: T.ink, margin: "8px 0 10px", fontWeight: 500 }}>Recevoir les nouvelles publications</h3>
            <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 14.5, color: T.inkSoft, margin: 0, lineHeight: 1.6 }}>
              Un e-mail occasionnel, sans bruit, quand un nouvel article ou rapport est publié.
            </p>
          </div>
          {sent ? (
            <div style={{ fontFamily: "'Inter', sans-serif", color: T.green, display: "flex", alignItems: "center", gap: 8, fontWeight: 600 }}><Check size={18} /> Merci, vous serez prévenu·e.</div>
          ) : (
            <form onSubmit={handleSubscribe} style={{ display: "flex", gap: 10, flexWrap: "wrap" }}>
              <input required type="email" value={email} onChange={e => setEmail(e.target.value)} placeholder="vous@exemple.com"
                style={{ padding: "13px 16px", border: `1px solid ${T.line}`, background: "#fff", color: T.ink, fontFamily: "'Inter', sans-serif", fontSize: 14, minWidth: 240, outline: "none" }} />
              <Btn type="submit" variant="green" style={{ opacity: submitting ? 0.7 : 1 }}>S'inscrire <Mail size={15} /></Btn>
            </form>
          )}
        </div>
      </Reveal>
    </div>
  );
}
