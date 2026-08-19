import React, { useEffect, useState } from "react";
import { Menu, X, Settings, ArrowUpRight } from "lucide-react";
import { T } from "../theme.js";
import NodeMark from "./NodeMark.jsx";
import BrandLogo from "./BrandLogo.jsx";

export default function NavBar({ page, setPage }) {
  const [open, setOpen] = useState(false);
  const [progress, setProgress] = useState(0);
  const links = [
    ["home", "Home"], ["works", "Works"], ["meet", "Meet Yewtod"],
    ["books", "Books"], ["collab", "Collaborations"],
  ];
  useEffect(() => {
    const updateProgress = () => {
      const max = document.documentElement.scrollHeight - window.innerHeight;
      setProgress(max > 0 ? (window.scrollY / max) * 100 : 0);
    };
    window.addEventListener("scroll", updateProgress, { passive: true });
    updateProgress();
    return () => window.removeEventListener("scroll", updateProgress);
  }, []);
  return (
    <div className="ytd-navbar" style={{ position: "sticky", top: 0, zIndex: 40, background: `${T.paper}F2`, backdropFilter: "blur(14px)", borderBottom: `1px solid ${T.line}` }}>
      <div className="ytd-scroll-progress" style={{ width: `${progress}%` }} />
      <div style={{ maxWidth: 1180, margin: "0 auto", padding: "15px 28px", display: "flex", alignItems: "center", justifyContent: "space-between", gap: 28 }}>
        <button onClick={() => setPage("home")} className="ytd-logo-btn" style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 10 }}>
          <BrandLogo />
        </button>

        <div className="ytd-desktop-nav ytd-nav-links" style={{ display: "flex", alignItems: "center", gap: 8, flex: 1, justifyContent: "center" }}>
          {links.map(([key, label]) => (
            <button key={key} onClick={() => setPage(key)} className={`ytd-nav-link ${page === key ? "ytd-nav-link-active" : ""}`}
              style={{
                background: "none", border: "none", cursor: "pointer", fontFamily: "'Inter', sans-serif",
                fontSize: 12, letterSpacing: "0.04em", textTransform: "uppercase", color: page === key ? T.ink : T.inkSoft, fontWeight: page === key ? 700 : 600,
                padding: "10px 13px", position: "relative",
              }}>{label}</button>
          ))}
        </div>
        <div className="ytd-desktop-nav" style={{ display: "flex", alignItems: "center", gap: 10 }}>
          <button onClick={() => setPage("collab")} className="ytd-admin-pill" style={{
            background: T.ink, border: `1px solid ${T.ink}`, borderRadius: 3, padding: "10px 14px",
            fontFamily: "'Space Mono', monospace", fontSize: 10.5, color: T.paper, cursor: "pointer",
            display: "flex", alignItems: "center", gap: 6,
          }}>Parlons projet <ArrowUpRight size={13} /></button>
          <button onClick={() => setPage("admin")} aria-label="Ouvrir l'administration" style={{ background: "none", border: "none", color: T.inkSoft, cursor: "pointer", padding: 6 }}><Settings size={15} /></button>
        </div>

        <button className="ytd-mobile-toggle" onClick={() => setOpen(!open)} style={{ display: "none", background: "none", border: "none", cursor: "pointer" }}>
          {open ? <X size={22} /> : <Menu size={22} />}
        </button>
      </div>
      <div className={`ytd-mobile-menu ${open ? "ytd-mobile-menu-open" : ""}`} style={{ borderTop: `1px solid ${T.line}`, padding: "12px 24px 20px", display: "flex", flexDirection: "column", gap: 14 }}>
          {links.map(([key, label]) => (
            <button key={key} onClick={() => { setPage(key); setOpen(false); }}
              style={{ background: "none", border: "none", textAlign: "left", cursor: "pointer", fontFamily: "'Inter', sans-serif", fontSize: 16, color: page === key ? T.ink : T.inkSoft, fontWeight: 600, padding: "4px 0" }}>
              {label}
            </button>
          ))}
          <button onClick={() => { setPage("collab"); setOpen(false); }} style={{ background: T.ink, border: "none", textAlign: "left", cursor: "pointer", fontFamily: "'Space Mono', monospace", fontSize: 12, color: T.paper, padding: "12px 14px" }}>Parlons projet <ArrowUpRight size={14} style={{ verticalAlign: "middle" }} /></button>
          <button onClick={() => { setPage("admin"); setOpen(false); }} className="ytd-mobile-admin-link" style={{ background: "none", border: `1px solid ${T.line}`, textAlign: "left", cursor: "pointer", fontFamily: "'JetBrains Mono', monospace", fontSize: 11, color: T.inkSoft, padding: "10px 14px" }}><Settings size={13} style={{ verticalAlign: "middle", marginRight: 7 }} /> Administration</button>
      </div>
    </div>
  );
}
