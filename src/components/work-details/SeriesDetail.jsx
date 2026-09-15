import React from "react";
import { useNavigate } from "react-router-dom";
import { Play } from "lucide-react";
import { T } from "../../theme.js";
import { workPath } from "../../lib/paths.js";
import { useSeriesEpisodes } from "../../hooks/useSeriesEpisodes.js";
import { Section, FieldList } from "./shared.jsx";
import MediaDisplay, { youtubeThumbnail } from "../MediaDisplay.jsx";

function EpisodeCard({ ep, onOpen }) {
  const thumb = ep.videoUrl ? youtubeThumbnail(ep.videoUrl) : null;
  return (
    <article
      onClick={onOpen} onKeyDown={e => e.key === "Enter" && onOpen()} role="button" tabIndex={0}
      style={{ cursor: "pointer" }}
    >
      <div style={{ position: "relative", aspectRatio: "16/9", background: T.paperAlt, border: `1px solid ${T.line}`, overflow: "hidden" }}>
        {thumb
          ? <img src={thumb} alt={ep.title} loading="lazy" onError={e => { e.currentTarget.src = thumb.replace("maxresdefault", "hqdefault"); }} style={{ width: "100%", height: "100%", objectFit: "cover" }} />
          : <div style={{ display: "grid", placeItems: "center", height: "100%", color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 12 }}>Pas de vidéo</div>}
        <span style={{ position: "absolute", inset: 0, display: "grid", placeItems: "center" }}>
          <span style={{ display: "grid", placeItems: "center", width: 46, height: 46, borderRadius: "50%", background: `${T.ink}CC` }}><Play size={18} color={T.paper} fill={T.paper} /></span>
        </span>
      </div>
      <div style={{ paddingTop: 12 }}>
        <span style={{ display: "block", fontFamily: "'JetBrains Mono', monospace", fontSize: 10.5, color: T.green, textTransform: "uppercase" }}>Épisode {ep.episodeNumber}</span>
        <strong style={{ display: "block", fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 15.5, fontWeight: 700, color: T.ink, margin: "5px 0 6px", lineHeight: 1.3 }}>{ep.title}</strong>
        <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, color: T.green }}>Regarder →</span>
      </div>
    </article>
  );
}

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
          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(220px, 1fr))", gap: 24 }}>
            {episodes.map(ep => <EpisodeCard key={ep.id} ep={ep} onOpen={() => navigate(workPath("episodes-documentaires", ep.slug))} />)}
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
