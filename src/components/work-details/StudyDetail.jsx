import React from "react";
import { T } from "../../theme.js";
import { fmtDate } from "../../lib/contentTypes.js";
import { Section, FieldList, FactRow } from "./shared.jsx";

export default function StudyDetail({ work }) {
  return (
    <>
      <p style={{ fontFamily: "'Fraunces', serif", fontWeight: 500, fontSize: 23, lineHeight: 1.45, color: T.ink, margin: "0 0 6px", paddingBottom: 26, borderBottom: `1px solid ${T.line}` }}>
        {work.researchQuestion}
      </p>
      <FactRow facts={[["Auteurs", work.authors], ["Publié le", fmtDate(work.date)]]} />

      <Section title="Contexte">{work.context}</Section>
      <Section title="Objectifs">{work.objectives}</Section>
      <Section title="Hypothèses">{work.hypotheses}</Section>
      <Section title="Méthodologie">{work.methodology}</Section>
      <Section title="Données utilisées">{work.dataUsed}</Section>
      <Section title="Analyses">{work.analyses}</Section>
      <Section title="Résultats">{work.results}</Section>
      <Section title="Discussion">{work.discussion}</Section>
      <Section title="Limites">{work.limitations}</Section>
      <Section title="Perspectives">{work.perspectives}</Section>
      <FieldList title="Bibliographie" value={work.bibliography} ordered />
    </>
  );
}
