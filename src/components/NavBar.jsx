import React, { useEffect, useRef, useState } from "react";
import { NavLink, useNavigate, useLocation } from "react-router-dom";
import { Menu, X, Settings, ArrowUpRight } from "lucide-react";
import { T } from "../theme.js";
import { PATHS } from "../lib/paths.js";
import BrandLogo from "./BrandLogo.jsx";

// CSS injected once at module load — no separate stylesheet to import.
const STYLE_ID = "ytd-navbar-styles";
if (typeof document !== "undefined" && !document.getElementById(STYLE_ID)) {
  const style = document.createElement("style");
  style.id = STYLE_ID;
  style.textContent = `
.ytd-navbar-scrolled {
  box-shadow: 0 8px 24px -16px rgba(20, 18, 14, 0.28);
}

/* Scroll progress bar */
.ytd-scroll-progress {
  height: 2px;
  background: linear-gradient(90deg, ${T.lime}, ${T.green});
}

/* Logo — pas d'opacité/transform/filter au survol : casserait le
   mix-blend-mode du logo inversé (contexte d'empilement, voir globalStyles.js) */

/* Desktop nav links */
.ytd-nav-link { will-change: color; }
.ytd-nav-link:hover { color: #ffffff !important; }

/* CTA pill */
.ytd-cta:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px -10px rgba(20, 18, 14, 0.45);
}
.ytd-cta:active { transform: translateY(0); }
.ytd-cta-icon { transition: transform 240ms cubic-bezier(0.22, 1, 0.36, 1); }
.ytd-cta:hover .ytd-cta-icon { transform: translate(2px, -2px); }

/* Admin / settings icon */
.ytd-icon-btn:hover {
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  transform: rotate(24deg);
}

/* Burger icon morph */
.ytd-burger-icon {
  display: inline-flex;
  transition: transform 260ms cubic-bezier(0.22, 1, 0.36, 1);
}
.ytd-burger-icon-open { transform: rotate(90deg); }

/* Mobile menu container: collapse/expand with fade */
.ytd-mobile-menu {
  display: flex;
  overflow: hidden;
  max-height: 0;
  opacity: 0;
  padding-top: 0 !important;
  padding-bottom: 0 !important;
  transform: translateY(-6px);
  transition:
    max-height 380ms cubic-bezier(0.22, 1, 0.36, 1),
    opacity 260ms ease,
    transform 320ms cubic-bezier(0.22, 1, 0.36, 1),
    padding 380ms cubic-bezier(0.22, 1, 0.36, 1);
}
.ytd-mobile-menu-open {
  max-height: 480px;
  opacity: 1;
  transform: translateY(0);
  padding-top: 12px !important;
  padding-bottom: 20px !important;
}

/* Staggered fade/slide-in for each mobile link, only while open */
.ytd-mobile-menu .ytd-mobile-link {
  opacity: 0;
  transform: translateY(-8px);
  transition: opacity 320ms ease, transform 320ms cubic-bezier(0.22, 1, 0.36, 1);
}
.ytd-mobile-menu-open .ytd-mobile-link {
  opacity: 1;
  transform: translateY(0);
}

@media (max-width: 860px) {
  .ytd-desktop-nav { display: none !important; }
  .ytd-mobile-toggle { display: inline-flex !important; align-items: center; justify-content: center; }
}

@media (min-width: 861px) {
  .ytd-mobile-menu { display: none !important; }
}

/* Respect reduced-motion preference */
@media (prefers-reduced-motion: reduce) {
  .ytd-navbar, .ytd-navbar-inner, .ytd-logo-btn, .ytd-nav-link, .ytd-cta, .ytd-cta-icon,
  .ytd-icon-btn, .ytd-burger-icon, .ytd-mobile-menu, .ytd-mobile-link, .ytd-scroll-progress,
  [aria-hidden="true"] {
    transition-duration: 1ms !important;
    animation-duration: 1ms !important;
  }
}
`;
  document.head.appendChild(style);
}

export default function NavBar() {
  const navigate = useNavigate();
  const location = useLocation();
  const [open, setOpen] = useState(false);
  const [progress, setProgress] = useState(0);
  const [scrolled, setScrolled] = useState(false);
  const [hovered, setHovered] = useState(null);
  const [indicator, setIndicator] = useState({ left: 0, width: 0, opacity: 0 });

  const navRef = useRef(null);
  const linkRefs = useRef({});

  const links = [
    [PATHS.home, "Home", true],
    [PATHS.works, "Works", false],
    [PATHS.meet, "Meet Yewtod", false],
    [PATHS.books, "Books", false],
    [PATHS.collab, "Collaborations", false],
  ];

  // Scroll progress + shrink-on-scroll
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

  // Sliding indicator: follows hover, falls back to the active route
  const moveIndicatorTo = (key) => {
    const el = linkRefs.current[key];
    const wrap = navRef.current;
    if (!el || !wrap) return;
    const elBox = el.getBoundingClientRect();
    const wrapBox = wrap.getBoundingClientRect();
    setIndicator({ left: elBox.left - wrapBox.left, width: elBox.width, opacity: 1 });
  };

  useEffect(() => {
    const activeKey = hovered ?? links.find(([to, , end]) => (end ? location.pathname === to : location.pathname.startsWith(to)))?.[0];
    if (activeKey) moveIndicatorTo(activeKey);
    else setIndicator((prev) => ({ ...prev, opacity: 0 }));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [hovered, location.pathname]);

  useEffect(() => {
    const onResize = () => {
      const activeKey = hovered ?? links.find(([to, , end]) => (end ? location.pathname === to : location.pathname.startsWith(to)))?.[0];
      if (activeKey) moveIndicatorTo(activeKey);
    };
    window.addEventListener("resize", onResize);
    return () => window.removeEventListener("resize", onResize);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [hovered, location.pathname]);

  // Lock body scroll while the mobile menu is open
  useEffect(() => {
    document.body.style.overflow = open ? "hidden" : "";
    return () => { document.body.style.overflow = ""; };
  }, [open]);

  const linkStyle = ({ isActive }) => ({
    background: "none", border: "none", cursor: "pointer", fontFamily: "'Inter', sans-serif",
    fontSize: 12, letterSpacing: "0.04em", textTransform: "uppercase", color: isActive ? "#fff" : "#ffffffB0", fontWeight: isActive ? 700 : 600,
    padding: "10px 13px", position: "relative", textDecoration: "none", display: "inline-block",
    transition: "color 220ms ease",
  });

  return (
    <div className={`ytd-navbar ${scrolled ? "ytd-navbar-scrolled" : ""}`} style={{ position: "sticky", top: 0, zIndex: 40, background: T.greenDeep, transition: "box-shadow 320ms ease, border-color 320ms ease" }}>
      <div className="ytd-scroll-progress" style={{ width: `${progress}%`, transition: "width 120ms linear" }} />

      <div className="ytd-navbar-inner" style={{ maxWidth: 1180, margin: "0 auto", padding: scrolled ? "11px 28px" : "15px 28px", display: "flex", alignItems: "center", justifyContent: "space-between", gap: 28, transition: "padding 320ms cubic-bezier(0.22, 1, 0.36, 1)" }}>
        <button onClick={() => navigate(PATHS.home)} className="ytd-logo-btn" style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 10, transition: "transform 260ms cubic-bezier(0.22, 1, 0.36, 1)" }}>
          <BrandLogo dark />
        </button>

        <div
          ref={navRef}
          className="ytd-desktop-nav ytd-nav-links"
          style={{ position: "relative", display: "flex", alignItems: "center", gap: 8, flex: 1, justifyContent: "center" }}
          onMouseLeave={() => setHovered(null)}
        >
          {/* sliding indicator */}
          <span
            aria-hidden="true"
            style={{
              position: "absolute",
              left: indicator.left,
              width: indicator.width,
              bottom: 4,
              height: 2,
              borderRadius: 2,
              background: T.lime,
              opacity: indicator.opacity,
              transform: `scaleX(${indicator.opacity ? 1 : 0.4})`,
              transformOrigin: "center",
              transition: "left 320ms cubic-bezier(0.22, 1, 0.36, 1), width 320ms cubic-bezier(0.22, 1, 0.36, 1), opacity 220ms ease, transform 320ms cubic-bezier(0.22, 1, 0.36, 1)",
              pointerEvents: "none",
            }}
          />
          {links.map(([to, label, end]) => (
            <NavLink
              key={to}
              to={to}
              end={end}
              ref={(el) => { linkRefs.current[to] = el; }}
              onMouseEnter={() => setHovered(to)}
              className={({ isActive }) => `ytd-nav-link ${isActive ? "ytd-nav-link-active" : ""}`}
              style={linkStyle}
            >
              {label}
            </NavLink>
          ))}
        </div>

        <div className="ytd-desktop-nav" style={{ display: "flex", alignItems: "center", gap: 10 }}>
          <button
            onClick={() => navigate(PATHS.collab)}
            className="ytd-admin-pill ytd-cta"
            style={{
              background: T.lime, border: `1px solid ${T.lime}`, borderRadius: 20, padding: "10px 18px",
              fontFamily: "'Inter', sans-serif", fontWeight: 700, fontSize: 12.5, color: T.greenDeep, cursor: "pointer",
              display: "flex", alignItems: "center", gap: 6,
              transition: "transform 240ms cubic-bezier(0.22, 1, 0.36, 1), box-shadow 240ms ease",
            }}
          >
            Parlons projet
            <ArrowUpRight size={13} className="ytd-cta-icon" />
          </button>
          <button onClick={() => navigate(PATHS.dashboard)} aria-label="Ouvrir l'administration" className="ytd-icon-btn" style={{ background: "none", border: "none", color: "#ffffffB0", cursor: "pointer", padding: 6, borderRadius: 3, transition: "color 200ms ease, background 200ms ease, transform 200ms ease" }}>
            <Settings size={15} />
          </button>
        </div>

        <button className="ytd-mobile-toggle" onClick={() => setOpen(!open)} aria-label={open ? "Fermer le menu" : "Ouvrir le menu"} style={{ display: "none", background: "none", border: "none", color: "#fff", cursor: "pointer", position: "relative", width: 22, height: 22 }}>
          <span className={`ytd-burger-icon ${open ? "ytd-burger-icon-open" : ""}`}>
            {open ? <X size={22} /> : <Menu size={22} />}
          </span>
        </button>
      </div>

      <div className={`ytd-mobile-menu ${open ? "ytd-mobile-menu-open" : ""}`} style={{ background: T.greenDeep, borderTop: "1px solid #ffffff22", padding: "12px 24px 20px", display: "flex", flexDirection: "column", gap: 14 }}>
        {links.map(([to, label], i) => (
          <NavLink
            key={to}
            to={to}
            onClick={() => setOpen(false)}
            className="ytd-mobile-link"
            style={({ isActive }) => ({
              background: "none", border: "none", textAlign: "left", cursor: "pointer", fontFamily: "'Inter', sans-serif",
              fontSize: 16, color: isActive ? "#fff" : "#ffffffB0", fontWeight: 600, padding: "4px 0", textDecoration: "none",
              transitionDelay: `${open ? i * 35 : 0}ms`,
            })}
          >
            {label}
          </NavLink>
        ))}
        <button
          onClick={() => { navigate(PATHS.collab); setOpen(false); }}
          className="ytd-mobile-link"
          style={{ background: T.lime, border: "none", borderRadius: 20, textAlign: "center", cursor: "pointer", fontFamily: "'Inter', sans-serif", fontWeight: 700, fontSize: 13, color: T.greenDeep, padding: "12px 14px", transitionDelay: `${open ? links.length * 35 : 0}ms` }}
        >
          Parlons projet <ArrowUpRight size={14} style={{ verticalAlign: "middle" }} />
        </button>
        <button
          onClick={() => { navigate(PATHS.dashboard); setOpen(false); }}
          className="ytd-mobile-admin-link ytd-mobile-link"
          style={{ background: "none", border: "1px solid #ffffff33", textAlign: "left", cursor: "pointer", fontFamily: "'JetBrains Mono', monospace", fontSize: 11, color: "#ffffffB0", padding: "10px 14px", transitionDelay: `${open ? (links.length + 1) * 35 : 0}ms` }}
        >
          <Settings size={13} style={{ verticalAlign: "middle", marginRight: 7 }} /> Administration
        </button>
      </div>
    </div>
  );
}