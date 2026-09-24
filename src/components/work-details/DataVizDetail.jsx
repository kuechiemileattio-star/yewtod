import React from "react";
import { T } from "../../theme.js";
import { Section, Pill, LinkAction } from "./shared.jsx";
import DataVizChart from "./DataVizChart.jsx";

export default function DataVizDetail({ work }) {
  const blocks = Array.isArray(work.blocks) ? work.blocks : [];
  // Entries created before the multi-block builder only had one chart —
  // still show it, so nothing published before this feature breaks.
  const legacyBlock = !blocks.length && work.csvFile
    ? [{ title: null, description: work.description, csvFile: work.csvFile, visualizationType: work.visualizationType }]
    : [];
  const items = blocks.length ? blocks : legacyBlock;

  return (
    <>
      {work.dataSource && (
        <div style={{ display: "flex", flexWrap: "wrap", gap: 8, marginBottom: 20 }}>
          <Pill>{work.dataSource}</Pill>
        </div>
      )}
      {!blocks.length && <p style={{ fontFamily: "'Fraunces', serif", fontWeight: 500, fontSize: 21, lineHeight: 1.5, color: T.ink, margin: "0 0 30px" }}>{work.description}</p>}

      {items.length === 0 && (
        <div className="ytd-dataviz-frame ytd-dataviz-status">Visualisation à venir — aucune donnée déposée pour l'instant.</div>
      )}

      {items.map((block, i) => (
        <div key={i} style={{ marginBottom: 30 }}>
          {block.title && <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 22, fontWeight: 500, margin: "0 0 6px", color: T.greenDeep }}>{block.title}</h2>}
          {block.description && <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15, color: T.inkSoft, lineHeight: 1.6, margin: "0 0 14px" }}>{block.description}</p>}
          <DataVizChart csvUrl={block.csvFile} type={block.visualizationType} />
        </div>
      ))}

      <Section title="Légende">{work.legend}</Section>
      <Section title="Analyse">{work.analysis}</Section>

      {work.sourceCodeUrl && (
        <div style={{ display: "flex", gap: 10, flexWrap: "wrap", marginTop: 20 }}>
          <LinkAction href={work.sourceCodeUrl}>Code source</LinkAction>
        </div>
      )}
    </>
  );
}
