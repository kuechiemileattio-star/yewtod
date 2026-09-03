import React from "react";
import { Navigate, useLocation } from "react-router-dom";
import { T } from "../theme.js";
import { PATHS } from "../lib/paths.js";
import { useAuth } from "../contexts/AuthContext.jsx";

function Centered({ children, pulse }) {
  return (
    <div style={{ minHeight: "60vh", display: "flex", alignItems: "center", justifyContent: "center", fontFamily: "'Inter', sans-serif", color: T.inkSoft, textAlign: "center", padding: 24 }}>
      {pulse
        ? <span className="ytd-admin-live" style={{ justifyContent: "center" }}><span /> {children}</span>
        : children}
    </div>
  );
}

export default function RequireAuth({ children, permission }) {
  const { session, profile, loading, hasPermission } = useAuth();
  const location = useLocation();

  if (loading) return <Centered pulse>Chargement…</Centered>;
  if (!session) return <Navigate to="/login" replace state={{ from: location.pathname }} />;
  if (profile?.status === "suspended") return <Centered>Votre accès a été suspendu. Contactez un administrateur.</Centered>;
  if (profile?.status === "invited") return <Navigate to="/onboarding" replace />;
  if (permission && !hasPermission(permission)) return <Centered>Vous n'avez pas la permission d'accéder à cette section.</Centered>;

  return children;
}
