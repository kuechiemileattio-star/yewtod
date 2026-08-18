import React from "react";
import { T } from "../theme.js";
import NodeMark from "../components/NodeMark.jsx";
import Reveal from "../components/Reveal.jsx";
import Tag from "../components/Tag.jsx";
import SectionLabel from "../components/SectionLabel.jsx";

export default function Meet() {
  const qa = [
    ["Pourquoi Yewtod SS ?", "Parce que les grands problèmes contemporains — inégalités, gouvernance, transition numérique — se comprennent rarement en une phrase, et méritent un espace d'écriture longue plutôt que des formats jetables."],
    ["Quelle est la vision ?", "Faire des sciences sociales appliquées un outil de décision aussi rigoureux que les sciences dures, sans en perdre l'accessibilité."],
    ["Quels sujets sont étudiés ?", "Systèmes complexes, économie du développement, politique publique, intelligence artificielle et innovation, avec un ancrage particulier sur l'Afrique de l'Ouest."],
    ["Quelle est la méthode de travail ?", "Lecture large, entretiens de terrain, modélisation quand c'est possible, et publication systématique — y compris des hypothèses qui ne se confirment pas."],
    ["Quels sont les projets futurs ?", "Une série documentaire annuelle, un programme de bourses de recherche pour jeunes chercheurs, et l'ouverture progressive de la plateforme à des contributeurs invités."],
  ];
  const values = ["Rigueur", "Curiosité", "Transparence", "Utilité publique", "Humilité intellectuelle"];

  return (
    <div style={{ maxWidth: 900, margin: "0 auto", padding: "80px 24px 110px" }}>
      <div style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 20 }}>
        <NodeMark />
        <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 12, letterSpacing: "0.12em", textTransform: "uppercase", color: T.green }}>Meet Yewtod</span>
      </div>
      <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(34px, 4.5vw, 50px)", fontWeight: 500, lineHeight: 1.1, margin: "0 0 48px" }}>
        Le projet, sa méthode et la personne derrière.
      </h1>

      <div style={{ display: "flex", flexDirection: "column", gap: 36, marginBottom: 80 }}>
        {qa.map(([q, a], i) => (
          <Reveal key={q} delay={i * 70}>
            <div style={{ display: "grid", gridTemplateColumns: "260px 1fr", gap: 28, paddingBottom: 32, borderBottom: `1px solid ${T.line}` }} className="ytd-qa-row">
              <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 21, fontWeight: 600, margin: 0 }}>{q}</h3>
              <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15.5, color: T.inkSoft, lineHeight: 1.65, margin: 0 }}>{a}</p>
            </div>
          </Reveal>
        ))}
      </div>

      <Reveal><SectionLabel>Le fondateur</SectionLabel></Reveal>
      <Reveal><div style={{ display: "grid", gridTemplateColumns: "220px 1fr", gap: 40 }} className="ytd-founder">
        <div style={{ width: 200, height: 240, background: T.paperAlt, border: `1px solid ${T.line}`, display: "flex", alignItems: "center", justifyContent: "center" }}>
          <NodeMark size={16} color={T.green} />
        </div>
        <div>
          <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 24, fontWeight: 600, margin: "0 0 16px" }}>Yewtod</h3>
          <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15.5, color: T.inkSoft, lineHeight: 1.7, margin: "0 0 16px" }}>
            Fondateur de Yewtod SS. Un parcours entre économie du développement, science des données et politiques publiques, nourri par plusieurs années de travail de terrain en Afrique de l'Ouest et par une pratique régulière de la modélisation de systèmes complexes.
          </p>
          <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15.5, color: T.inkSoft, lineHeight: 1.7, margin: "0 0 20px" }}>
            Domaines d'intérêt : gouvernance des données, économie institutionnelle, IA appliquée aux politiques publiques. Ambition : faire de Yewtod SS un pont durable entre recherche académique et décision publique.
          </p>
          <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>{values.map(v => <Tag key={v}>{v}</Tag>)}</div>
        </div>
      </div></Reveal>
    </div>
  );
}
