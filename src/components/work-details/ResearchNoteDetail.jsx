import React from "react";
import { T } from "../../theme.js";
import { Section, FieldList, Pill } from "./shared.jsx";

const PROGRESS_LABELS = { idee: "Idée", en_cours: "En cours", abouti: "Abouti" };

export default function ResearchNoteDetail({ work }) {
  return (
    <>
      <div style={{ marginBottom: 22 }}><Pill tone={T.lime}>{PROGRESS_LABELS[work.progressStatus] || work.progressStatus}</Pill></div>
      <p style={{ fontFamily: "'Fraunces', serif", fontWeight: 500, fontSize: 21, lineHeight: 1.5, color: T.ink, margin: "0 0 34px", paddingBottom: 26, borderBottom: `1px solid ${T.line}` }}>
        {work.mainIdea}
      </p>
      <Section title="Contexte">{work.context}</Section>
      <Section title="Observations">{work.observations}</Section>
      <Section title="Hypothèses">{work.hypotheses}</Section>
      <FieldList title="Schémas" value={work.diagrams} />
      <Section title="Notes personnelles">{work.personalNotes}</Section>
      <FieldList title="Liens utiles" value={work.usefulLinks} />
      <FieldList title="Références" value={work.references} />
    </>
  );
}
