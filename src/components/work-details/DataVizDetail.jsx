import React from "react";
import { T } from "../../theme.js";
import { Section, Pill, LinkAction } from "./shared.jsx";

export default function DataVizDetail({ work }) {
  return (
    <>
      <div style={{ display: "flex", flexWrap: "wrap", gap: 8, marginBottom: 20 }}>
        {work.dataSource && <Pill>{work.dataSource}</Pill>}
        {work.visualizationType && <Pill tone={T.lime}>{work.visualizationType}</Pill>}
      </div>
      <p style={{ fontFamily: "'Fraunces', serif", fontWeight: 500, fontSize: 21, lineHeight: 1.5, color: T.ink, margin: "0 0 30px" }}>{work.description}</p>

      {(work.chartConfig && Object.keys(work.chartConfig).length > 0) ? (
        <div style={{ padding: 24, border: `1px dashed ${T.line}`, background: T.paperAlt, marginBottom: 30, color: T.inkSoft, fontFamily: "'JetBrains Mono', monospace", fontSize: 12, textAlign: "center" }}>
          Graphique interactif — configuration disponible, rendu à intégrer.
        </div>
      ) : (
        <div style={{ padding: 40, border: `1px dashed ${T.line}`, background: T.paperAlt, marginBottom: 30, color: T.inkSoft, fontFamily: "'JetBrains Mono', monospace", fontSize: 12, textAlign: "center", textTransform: "uppercase" }}>
          Visualisation à venir
        </div>
      )}

      <Section title="Légende">{work.legend}</Section>
      <Section title="Analyse">{work.analysis}</Section>

      <div style={{ display: "flex", gap: 10, flexWrap: "wrap", marginTop: 20 }}>
        {work.csvFile && <LinkAction href={work.csvFile} download>Télécharger les données (CSV)</LinkAction>}
        {work.imageFile && <LinkAction href={work.imageFile} download>Télécharger l'image</LinkAction>}
        {work.sourceCodeUrl && <LinkAction href={work.sourceCodeUrl}>Code source</LinkAction>}
      </div>
    </>
  );
}
