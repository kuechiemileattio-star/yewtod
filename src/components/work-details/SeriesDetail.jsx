import React from "react";
import { useNavigate } from "react-router-dom";
import { PlayCircle } from "lucide-react";
import { T } from "../../theme.js";
import { workPath } from "../../lib/paths.js";
import { useSeriesEpisodes } from "../../hooks/useSeriesEpisodes.js";
import { Section, FieldList } from "./shared.jsx";
import MediaDisplay from "../MediaDisplay.jsx";

export default function SeriesDetail({ work }) {
  const navigate = useNavigate();
  const { episodes, loading } = useSeriesEpisodes(work.id);

  return (
    <>
      <p style={{ fontFamily: "'Fraunces', serif", fontWeight: 500, fontSize: 21, lineHeight: 1.5, color: T.ink, margin: "0 0 26px" }}>{work.description}</p>
      {work.trailerUrl && <div style={{ marginBottom: 34 }}><MediaDisplay type="video" url={work.trailerUrl} alt={`Bande-annonce · ${work.title}`} /></div>}
      <Section title="Thématique">{work.theme}</Section>

      <section className="ytd-work-detail-section">
        <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 24, fontWeight: 500, margin: "0 0 16px", color: T.greenDeep }}>Épisodes</h2>
        {loading ? <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif" }}>Chargement…</p> : (
          <div style={{ display: "grid", gap: 10 }}>
            {episodes.map(ep => (
              <button key={ep.id} onClick={() => navigate(workPath("episodes-documentaires", ep.slug))} style={{ display: "flex", alignItems: "center", gap: 16, padding: "16px 18px", border: `1px solid ${T.line}`, background: T.paper, cursor: "pointer", textAlign: "left", transition: "transform .2s ease, border-color .2s ease" }} onMouseEnter={e => { e.currentTarget.style.transform = "translateX(4px)"; e.currentTarget.style.borderColor = T.green; }} onMouseLeave={e => { e.currentTarget.style.transform = "none"; e.currentTarget.style.borderColor = T.line; }}>
                <PlayCircle size={22} color={T.green} style={{ flexShrink: 0 }} />
                <div>
                  <span style={{ display: "block", fontFamily: "'JetBrains Mono', monospace", fontSize: 10.5, color: T.inkSoft, textTransform: "uppercase" }}>Épisode {ep.episodeNumber}</span>
                  <strong style={{ fontFamily: "'Fraunces', serif", fontSize: 18, color: T.ink }}>{ep.title}</strong>
                </div>
              </button>
            ))}
            {episodes.length === 0 && <p style={{ color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 14 }}>Aucun épisode publié pour le moment.</p>}
          </div>
        )}
      </section>

      <FieldList title="Invités & intervenants" value={work.guests} />
      <FieldList title="Ressources complémentaires" value={work.additionalResources} />
      <FieldList title="Références" value={work.references} />
    </>
  );
}
