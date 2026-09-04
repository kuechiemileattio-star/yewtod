import React, { useState } from "react";
import { NavLink, Outlet, useLocation, useNavigate } from "react-router-dom";
import { LayoutGrid, FileText, BookOpen, Handshake, Settings, Users, LogOut, ArrowLeft, User, Menu, X } from "lucide-react";
import { T } from "../../theme.js";
import { PATHS } from "../../lib/paths.js";
import { useAuth } from "../../contexts/AuthContext.jsx";
import BrandLogo from "../../components/BrandLogo.jsx";

const MODULES = [
  { to: "", label: "Vue d'ensemble", icon: LayoutGrid, permission: null, end: true },
  { to: "publications", label: "Publications", icon: FileText, permission: "manage_articles" },
  { to: "books", label: "Livres", icon: BookOpen, permission: "manage_books" },
  { to: "collaborations", label: "Collaborations", icon: Handshake, permission: "manage_collaborations" },
  { to: "settings", label: "Paramètres", icon: Settings, permission: "manage_settings" },
  { to: "users", label: "Utilisateurs & rôles", icon: Users, permission: "manage_users" },
  { to: "profile", label: "Mon profil", icon: User, permission: null },
];

export default function DashboardShell() {
  const { profile, hasPermission, signOut } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const visibleModules = MODULES.filter(m => !m.permission || hasPermission(m.permission));
  const currentModule = visibleModules.find(m => (m.end ? location.pathname.endsWith("/dashboard") : location.pathname.includes(`/${m.to}`))) || visibleModules[0];

  async function handleSignOut() {
    await signOut();
    navigate(PATHS.home);
  }

  return (
    <div className="ytd-admin-shell" style={{ display: "flex", minHeight: "100vh", background: T.paper }}>
      {mobileNavOpen && <div className="ytd-admin-sidebar-backdrop" onClick={() => setMobileNavOpen(false)} />}

      <aside className={`ytd-admin-sidebar ${mobileNavOpen ? "ytd-admin-sidebar-open" : ""}`} style={{ width: 250, flexShrink: 0, padding: "28px 18px", position: "sticky", top: 0, height: "100vh", background: T.greenDeep }}>
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: 30 }}>
          <button onClick={() => navigate(PATHS.home)} className="ytd-admin-back" style={{ background: "none", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: 8, fontFamily: "'Inter', sans-serif", fontSize: 13, color: `${T.paper}AA` }}>
            <ArrowLeft size={14} /> Retour au site
          </button>
          <button onClick={() => setMobileNavOpen(false)} className="ytd-admin-sidebar-close" aria-label="Fermer le menu" style={{ display: "none", background: "none", border: "none", color: T.paper, cursor: "pointer" }}>
            <X size={18} />
          </button>
        </div>
        <div style={{ display: "flex", alignItems: "center", gap: 9, marginBottom: 34, padding: "0 4px" }}>
          <BrandLogo dark />
          <span style={{ fontFamily: "'Space Mono', monospace", fontSize: 9.5, color: T.lime, border: `1px solid ${T.lime}66`, padding: "2px 6px", borderRadius: 10 }}>CMS</span>
        </div>
        {visibleModules.map(({ to, label, icon: Icon, end }) => (
          <NavLink key={to || "overview"} to={to} end={end} onClick={() => setMobileNavOpen(false)} className="ytd-admin-tab" style={({ isActive }) => ({
            width: "100%", display: "flex", alignItems: "center", gap: 11, padding: "10px 12px", marginBottom: 4,
            background: isActive ? T.lime : "transparent", border: "none", borderLeft: isActive ? `3px solid ${T.paper}` : "3px solid transparent", borderRadius: 4, textDecoration: "none",
            fontFamily: "'Manrope', sans-serif", fontSize: 13, color: isActive ? T.ink : `${T.paper}BB`, fontWeight: isActive ? 800 : 600,
          })}>
            <Icon size={16} /> {label}
          </NavLink>
        ))}
        <button onClick={handleSignOut} className="ytd-admin-tab" style={{ width: "100%", display: "flex", alignItems: "center", gap: 11, padding: "10px 12px", marginTop: 24, background: "transparent", border: "none", borderTop: `1px solid ${T.paper}22`, borderRadius: 4, cursor: "pointer", fontFamily: "'Manrope', sans-serif", fontSize: 13, color: `${T.paper}99`, fontWeight: 600 }}>
          <LogOut size={16} /> Se déconnecter
        </button>
      </aside>

      <main className="ytd-admin-main" style={{ flex: 1, padding: "28px 44px 70px", minWidth: 0 }}>
        <div className="ytd-admin-topbar">
          <div style={{ display: "flex", alignItems: "center", gap: 14, minWidth: 0 }}>
            <button onClick={() => setMobileNavOpen(true)} className="ytd-admin-mobile-toggle" aria-label="Ouvrir le menu" style={{ display: "none", background: "none", border: `1px solid ${T.line}`, borderRadius: 6, padding: 8, color: T.ink, cursor: "pointer", flexShrink: 0 }}>
              <Menu size={18} />
            </button>
            <div style={{ minWidth: 0 }}>
              <span className="ytd-admin-kicker">Yewtod SS / CMS</span>
              <strong style={{ display: "block", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{currentModule?.label}</strong>
            </div>
          </div>
          <button onClick={() => navigate("profile")} style={{ display: "flex", alignItems: "center", gap: 10, border: "none", background: "none", cursor: "pointer", flexShrink: 0 }} aria-label="Voir mon profil">
            <span className="ytd-admin-live"><span /> {profile?.role?.name || "Membre"}</span>
            {profile?.avatar_url
              ? <img src={profile.avatar_url} alt="" style={{ width: 30, height: 30, borderRadius: "50%", objectFit: "cover", border: `1px solid ${T.line}` }} />
              : <span style={{ display: "grid", placeItems: "center", width: 30, height: 30, borderRadius: "50%", background: T.greenDeep, color: T.paper, fontSize: 12, fontWeight: 700, fontFamily: "'Plus Jakarta Sans', sans-serif" }}>{(profile?.full_name || profile?.email || "?").slice(0, 1).toUpperCase()}</span>}
          </button>
        </div>
        <Outlet />
      </main>
    </div>
  );
}
