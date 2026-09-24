import React from "react";
import { T } from "../../theme.js";
import { Section, Pill, LinkAction } from "./shared.jsx";
import DataVizChart from "./DataVizChart.jsx";

const TYPE_LABELS = { bar: "Barres", line: "Lignes", pie: "Camembert" };

export default function DataVizDetail({ work }) {
  return (
    <>
      <div style={{ display: "flex", flexWrap: "wrap", gap: 8, marginBottom: 20 }}>
        {work.dataSource && <Pill>{work.dataSource}</Pill>}
        {work.visualizationType && <Pill tone={T.lime}>{TYPE_LABELS[work.visualizationType] || work.visualizationType}</Pill>}
      </div>
      <p style={{ fontFamily: "'Fraunces', serif", fontWeight: 500, fontSize: 21, lineHeight: 1.5, color: T.ink, margin: "0 0 30px" }}>{work.description}</p>

      {work.csvFile
        ? <DataVizChart csvUrl={work.csvFile} type={work.visualizationType} />
        : <div className="ytd-dataviz-frame ytd-dataviz-status">Visualisation à venir — aucun fichier de données déposé pour l'instant.</div>}

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
