import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { FileText, BookOpen, Inbox, TrendingUp } from "lucide-react";
import { T } from "../../theme.js";
import { supabase } from "../../lib/supabaseClient.js";
import { CONTENT_TYPES } from "../../lib/contentTypes.js";
import { useAuth } from "../../contexts/AuthContext.jsx";

async function countRows(table, filters = {}) {
  let query = supabase.from(table).select("*", { count: "exact", head: true });
  for (const [key, value] of Object.entries(filters)) query = query.eq(key, value);
  const { count, error } = await query;
  return error ? 0 : count || 0;
}

export default function Overview() {
  const { profile, hasPermission } = useAuth();
  const navigate = useNavigate();
  const [stats, setStats] = useState(null);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const worksCounts = await Promise.all(CONTENT_TYPES.map(t => countRows(t.table)));
      const totalWorks = worksCounts.reduce((sum, n) => sum + n, 0);
      const [books, collabsTotal, collabsNew] = await Promise.all([
        countRows("books"),
        countRows("collaborations"),
        countRows("collaborations", { status: "nouveau" }),
      ]);
      if (!cancelled) setStats({ totalWorks, books, collabsTotal, collabsNew });
    })();
    return () => { cancelled = true; };
  }, []);

  const cards = [
    { label: "Publications (tous types)", value: stats?.totalWorks, icon: FileText },
    hasPermission("manage_books") && { label: "Livres référencés", value: stats?.books, icon: BookOpen, onClick: () => navigate("books") },
    hasPermission("manage_collaborations") && { label: "Collaborations reçues", value: stats?.collabsTotal, icon: Inbox, onClick: () => navigate("collaborations") },
    hasPermission("manage_collaborations") && { label: "À traiter", value: stats?.collabsNew, icon: TrendingUp, onClick: () => navigate("collaborations") },
  ].filter(Boolean);

  return (
    <section className="ytd-dashboard-new ytd-admin-view">
      <div className="ytd-dashboard-hero">
        <div>
          <span className="ytd-dashboard-eyebrow">Vue éditoriale</span>
          <h1>Bonjour, {profile?.full_name || "membre"}.</h1>
          <p>Voici ce qui se passe dans votre espace, limité aux sections de votre rôle ({profile?.role?.name}).</p>
        </div>
      </div>

      <div className="ytd-dashboard-stat-grid">
        {cards.map(({ label, value, icon: Icon, onClick }, index) => (
          <article key={label} className="ytd-dashboard-stat" onClick={onClick} style={{ animationDelay: `${index * 70}ms`, cursor: onClick ? "pointer" : "default" }}>
            <div className="ytd-dashboard-stat-top"><span>{label}</span><Icon size={18} /></div>
            <strong>{value ?? "…"}</strong>
            <small>{onClick ? "Voir le détail" : "Toutes catégories"}</small>
          </article>
        ))}
      </div>

      <p style={{ marginTop: 32, color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 13, maxWidth: 560 }}>
        La gestion détaillée des articles, rapports, études, notes de recherche, séries documentaires, expérimentations et visualisations de données arrive dans une prochaine itération — les livres et les collaborations sont déjà pleinement gérables.
      </p>
    </section>
  );
}
