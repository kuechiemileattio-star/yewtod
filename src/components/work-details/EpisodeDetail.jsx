import React from "react";
import { useNavigate } from "react-router-dom";
import { ArrowLeft } from "lucide-react";
import { T } from "../../theme.js";
import { workPath } from "../../lib/paths.js";
import { useEpisodeSeries } from "../../hooks/useSeriesEpisodes.js";
import { Section, FieldList } from "./shared.jsx";
import MediaDisplay from "../MediaDisplay.jsx";

export default function EpisodeDetail({ work }) {
  const navigate = useNavigate();
  const series = useEpisodeSeries(work.seriesId);
  const chapters = Array.isArray(work.chapters) ? work.chapters : [];

  return (
    <>
      {series && (
        <button onClick={() => navigate(workPath("series-documentaires", series.slug))} style={{ display: "inline-flex", alignItems: "center", gap: 6, marginBottom: 20, border: 0, background: "none", color: T.green, cursor: "pointer", fontFamily: "'JetBrains Mono', monospace", fontSize: 11.5 }}>
          <ArrowLeft size={13} /> Retour à la série · {series.title}
        </button>
      )}
      {work.videoUrl && <div style={{ marginBottom: 30 }}><MediaDisplay type="video" url={work.videoUrl} alt={work.title} /></div>}
      <p style={{ fontFamily: "'Fraunces', serif", fontWeight: 500, fontSize: 21, lineHeight: 1.5, color: T.ink, margin: "0 0 26px" }}>{work.summary}</p>

      {chapters.length > 0 && (
        <section className="ytd-work-detail-section">
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 24, fontWeight: 500, margin: "0 0 14px", color: T.greenDeep }}>Chapitres</h2>
          <div style={{ display: "grid", gap: 2 }}>
            {chapters.map((c, i) => (
              <div key={i} style={{ display: "flex", gap: 16, padding: "10px 0", borderTop: i === 0 ? "none" : `1px solid ${T.line}` }}>
                <span style={{ fontFamily: "'JetBrains Mono', monospace", fontSize: 13, color: T.green, minWidth: 52 }}>{c.timestamp}</span>
                <span style={{ fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 14.5, color: T.ink }}>{c.title}</span>
              </div>
            ))}
          </div>
        </section>
      )}

      <FieldList title="Intervenants" value={work.speakers} />
      <Section title="Transcription">{work.transcript}</Section>
      <FieldList title="Documents associés" value={work.relatedDocuments} />
      <FieldList title="Références" value={work.references} />
    </>
  );
}
