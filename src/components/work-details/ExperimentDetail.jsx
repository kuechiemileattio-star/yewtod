import React from "react";
import { Github } from "lucide-react";
import { T } from "../../theme.js";
import { Section, FieldList, LinkAction } from "./shared.jsx";

function TagRow({ value }) {
  const items = (value || "").split("\n").map(s => s.trim()).filter(Boolean);
  if (!items.length) return null;
  return (
    <div style={{ display: "flex", flexWrap: "wrap", gap: 8, margin: "0 0 30px" }}>
      {items.map(item => (
        <span key={item} style={{ padding: "6px 11px", border: `1px solid ${T.line}`, borderRadius: 20, fontFamily: "'JetBrains Mono', monospace", fontSize: 11.5, color: T.inkSoft }}>{item}</span>
      ))}
    </div>
  );
}

export default function ExperimentDetail({ work }) {
  return (
    <>
      <p style={{ fontFamily: "'Fraunces', serif", fontWeight: 500, fontSize: 21, lineHeight: 1.5, color: T.ink, margin: "0 0 20px" }}>{work.objective}</p>
      {work.sourceCodeUrl && <div style={{ marginBottom: 30 }}><LinkAction href={work.sourceCodeUrl}><Github size={14} style={{ marginRight: 2, verticalAlign: "-2px" }} />Voir le code source</LinkAction></div>}

      <Section title="Problématique">{work.problemStatement}</Section>
      <Section title="Protocole expérimental">{work.protocol}</Section>

      {work.toolsUsed && (
        <section className="ytd-work-detail-section">
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 24, fontWeight: 500, margin: "0 0 12px", color: T.greenDeep }}>Outils utilisés</h2>
          <TagRow value={work.toolsUsed} />
        </section>
      )}

      <FieldList title="Jeux de données" value={work.datasets} />
      <FieldList title="Captures d'écran" value={work.screenshots} />
      <FieldList title="Visualisations" value={work.visualizations} />
      <Section title="Résultats">{work.results}</Section>
      <Section title="Analyse">{work.analysis}</Section>
      <Section title="Limites">{work.limitations}</Section>
      <Section title="Conclusion">{work.conclusion}</Section>
      <FieldList title="Fichiers téléchargeables" value={work.downloadableFiles} />
    </>
  );
}
