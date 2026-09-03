import React, { useEffect, useState } from "react";
import { NavLink, useNavigate } from "react-router-dom";
import { Menu, X, Settings, ArrowUpRight } from "lucide-react";
import { T } from "../theme.js";
import { PATHS } from "../lib/paths.js";
import BrandLogo from "./BrandLogo.jsx";

export default function NavBar() {
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);
  const [progress, setProgress] = useState(0);
  const [scrolled, setScrolled] = useState(false);
  const links = [
    [PATHS.home, "Home", true],
    [PATHS.works, "Works", false],
    [PATHS.meet, "Meet Yewtod", false],
    [PATHS.books, "Books", false],
    [PATHS.collab, "Collaborations", false],
  ];
  useEffect(() => {
    const updateProgress = () => {
      const max = document.documentElement.scrollHeight - window.innerHeight;
      setProgress(max > 0 ? (window.scrollY / max) * 100 : 0);
      setScrolled(window.scrollY > 8);
    };
    window.addEventListener("scroll", updateProgress, { passive: true });
    updateProgress();
    return () => window.removeEventListener("scroll", updateProgress);
  }, []);
  const linkStyle = ({ isActive }) => ({
    background: "none", border: "none", cursor: "pointer", fontFamily: "'Inter', sans-serif",
    fontSize: 12, letterSpacing: "0.04em", textTransform: "uppercase", color: isActive ? T.ink : T.inkSoft, fontWeight: isActive ? 700 : 600,
    padding: "10px 13px", position: "relative", textDecoration: "none", display: "inline-block",
  });
  return (
    <div className={`ytd-navbar ${scrolled ? "ytd-navbar-scrolled" : ""}`} style={{ position: "sticky", top: 0, zIndex: 40, background: `${T.paper}F2`, backdropFilter: "blur(14px)", borderBottom: `1px solid ${T.line}` }}>
      <div className="ytd-scroll-progress" style={{ width: `${progress}%` }} />
      <div style={{ maxWidth: 1180, margin: "0 auto", padding: "15px 28px", display: "flex", alignItems: "center", justifyContent: "space-between", gap: 28 }}>
        <button onClick={() => navigate(PATHS.home)} className="ytd-logo-btn" style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 10 }}>
          <BrandLogo />
        </button>

        <div className="ytd-desktop-nav ytd-nav-links" style={{ display: "flex", alignItems: "center", gap: 8, flex: 1, justifyContent: "center" }}>
          {links.map(([to, label, end]) => (
            <NavLink key={to} to={to} end={end} className={({ isActive }) => `ytd-nav-link ${isActive ? "ytd-nav-link-active" : ""}`} style={linkStyle}>{label}</NavLink>
          ))}
        </div>
        <div className="ytd-desktop-nav" style={{ display: "flex", alignItems: "center", gap: 10 }}>
          <button onClick={() => navigate(PATHS.collab)} className="ytd-admin-pill" style={{
            background: T.ink, border: `1px solid ${T.ink}`, borderRadius: 3, padding: "10px 14px",
            fontFamily: "'Space Mono', monospace", fontSize: 10.5, color: T.paper, cursor: "pointer",
            display: "flex", alignItems: "center", gap: 6,
          }}>Parlons projet <ArrowUpRight size={13} /></button>
          <button onClick={() => navigate(PATHS.dashboard)} aria-label="Ouvrir l'administration" style={{ background: "none", border: "none", color: T.inkSoft, cursor: "pointer", padding: 6 }}><Settings size={15} /></button>
        </div>

        <button className="ytd-mobile-toggle" onClick={() => setOpen(!open)} style={{ display: "none", background: "none", border: "none", cursor: "pointer" }}>
          {open ? <X size={22} /> : <Menu size={22} />}
        </button>
      </div>
      <div className={`ytd-mobile-menu ${open ? "ytd-mobile-menu-open" : ""}`} style={{ borderTop: `1px solid ${T.line}`, padding: "12px 24px 20px", display: "flex", flexDirection: "column", gap: 14 }}>
          {links.map(([to, label]) => (
            <NavLink key={to} to={to} onClick={() => setOpen(false)}
              style={({ isActive }) => ({ background: "none", border: "none", textAlign: "left", cursor: "pointer", fontFamily: "'Inter', sans-serif", fontSize: 16, color: isActive ? T.ink : T.inkSoft, fontWeight: 600, padding: "4px 0", textDecoration: "none" })}>
              {label}
            </NavLink>
          ))}
          <button onClick={() => { navigate(PATHS.collab); setOpen(false); }} style={{ background: T.ink, border: "none", textAlign: "left", cursor: "pointer", fontFamily: "'Space Mono', monospace", fontSize: 12, color: T.paper, padding: "12px 14px" }}>Parlons projet <ArrowUpRight size={14} style={{ verticalAlign: "middle" }} /></button>
          <button onClick={() => { navigate(PATHS.dashboard); setOpen(false); }} className="ytd-mobile-admin-link" style={{ background: "none", border: `1px solid ${T.line}`, textAlign: "left", cursor: "pointer", fontFamily: "'JetBrains Mono', monospace", fontSize: 11, color: T.inkSoft, padding: "10px 14px" }}><Settings size={13} style={{ verticalAlign: "middle", marginRight: 7 }} /> Administration</button>
      </div>
    </div>
  );
}
