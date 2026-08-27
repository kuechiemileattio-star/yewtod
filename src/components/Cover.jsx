import React from "react";
import { T } from "../theme.js";

export default function Cover({ tone, label, tall, image, title }) {
  const images = {
    Articles: "https://images.unsplash.com/photo-1495020689067-958852a7765e?auto=format&fit=crop&w=1200&q=85",
    Rapports: "https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=85",
    "Notes de recherche": "https://images.unsplash.com/photo-1456324504439-367cee3b3c32?auto=format&fit=crop&w=1200&q=85",
    Études: "https://images.unsplash.com/photo-1500534623283-312aade485b7?auto=format&fit=crop&w=1200&q=85",
    "Visualisations de données": "https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=85",
    Expérimentations: "https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=1200&q=85",
    "Séries documentaires": "https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&fit=crop&w=1200&q=85",
  };
  return (
    <div
      className="ytd-cover"
      style={{
        background: `linear-gradient(155deg, ${tone}14 0%, ${tone}22 60%, ${tone}10 100%)`,
        border: `1px solid ${T.line}`,
        height: tall ? 260 : 168,
        borderRadius: 2,
        position: "relative",
        overflow: "hidden",
        display: "flex", alignItems: "flex-end",
      }}
    >
      {(image || images[label]) && <img loading="lazy" src={image || images[label]} alt="" style={{ position: "absolute", inset: 0, width: "100%", height: "100%", objectFit: "cover", filter: "saturate(.78) contrast(.96)", opacity: 0.86 }} />}
      <div style={{ position: "absolute", inset: 0, background: `linear-gradient(180deg, transparent 35%, ${T.ink}99 100%)` }} />
      <svg className="ytd-cover-lines" width="100%" height="100%" style={{ position: "absolute", inset: 0, opacity: 0.5 }} preserveAspectRatio="none">
        <line x1="0%" y1="30%" x2="100%" y2="10%" stroke={tone} strokeWidth="1" opacity="0.25" />
        <line x1="0%" y1="75%" x2="100%" y2="55%" stroke={tone} strokeWidth="1" opacity="0.2" />
        <circle cx="18%" cy="30%" r="3" fill={tone} opacity="0.5" />
        <circle cx="82%" cy="10%" r="3" fill={tone} opacity="0.5" />
        <circle cx="60%" cy="75%" r="3" fill={tone} opacity="0.4" />
      </svg>
      {title && <strong className="ytd-cover-title">{title}</strong>}
      <span className="ytd-cover-label" style={{
        fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, letterSpacing: "0.06em",
        textTransform: "uppercase", color: T.paper, background: `${T.ink}D9`, padding: "5px 9px",
        margin: 12, border: `1px solid ${T.line}`,
      }}>{label}</span>
    </div>
  );
}
