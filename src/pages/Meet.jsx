import React from "react";
import { T } from "../theme.js";
import NodeMark from "../components/NodeMark.jsx";
import Reveal from "../components/Reveal.jsx";
import Tag from "../components/Tag.jsx";
import SectionLabel from "../components/SectionLabel.jsx";
import { ArrowDownRight, BookOpen, Network, Target } from "lucide-react";
import useDocumentMeta from "../hooks/useDocumentMeta.js";

export default function Meet() {
  useDocumentMeta("Meet Yewtod", "Pourquoi Yewtod SS existe, sa méthode de travail et le parcours de son fondateur.");
  const qa = [
    ["Pourquoi ce site ?", "Parce que je passais mon temps à prendre des notes de lecture pour moi-même, et qu'à un moment j'ai trouvé bête de ne pas les rendre publiques."],
    ["C'est un média ou un journal perso ?", "Un peu des deux, honnêtement. Le format est celui d'un média, mais je n'ai pas de rédaction ni de ligne éditoriale imposée — juste ce qui m'occupe l'esprit ce mois-ci."],
    ["Quels sujets reviennent le plus ?", "Systèmes complexes, économie du développement, politiques publiques et IA, avec un biais assumé pour l'Afrique de l'Ouest, la région que je connais le mieux."],
    ["Comment tu travailles ?", "Je lis beaucoup, je parle à des gens qui savent des choses que je ne sais pas, et je modélise quand ça a du sens. Ce qui ne tient pas la route finit publié quand même, avec les limites indiquées."],
    ["Et après ?", "J'aimerais lancer une série documentaire, ouvrir la plateforme à d'autres contributeurs, et un jour financer de petites bourses de recherche. Rien n'est encore acté."],
  ];

  return (
    <div className="ytd-meet-page" style={{ maxWidth: 1060, margin: "0 auto", padding: "72px 24px 110px" }}>
      <header className="ytd-meet-hero">
      <div className="ytd-meet-hero-copy">
      <div style={{ marginBottom: 20 }}>
        <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 12, letterSpacing: "0.12em", textTransform: "uppercase", color: T.green }}>Meet Yewtod</span>
      </div>
      <h1 style={{ fontFamily: "'Newsreader', serif", fontSize: "clamp(38px, 5vw, 64px)", fontWeight: 500, lineHeight: 1.02, margin: "0 0 20px" }}>
        Un carnet de recherche, pas un magazine.
      </h1>
      <p className="ytd-meet-lead">Je publie ici ce que je lis, ce que je vérifie et ce que je n'ai pas encore complètement compris — dans cet ordre-là, souvent.</p>
      </div>
      <div className="ytd-meet-hero-mark"><NodeMark size={22} color={T.paper} /><span>Recherche<br />appliquée</span><ArrowDownRight size={22} /></div>
      </header>

      <section className="ytd-meet-method">
        <SectionLabel>Comment je travaille</SectionLabel>
        <div className="ytd-meet-method-grid">
          {[[BookOpen, "Je lis avant d'écrire", "Souvent trop, d'ailleurs — un article me prend en général plus de temps en lecture qu'en rédaction."], [Network, "Je croise les sources", "Un chiffre seul ne veut rien dire ; je préfère trois sources qui se contredisent un peu à une seule qui semble trop nette."], [Target, "Je publie même quand ce n'est pas parfait", "Une analyse utile aujourd'hui vaut mieux qu'une analyse parfaite dans six mois."]].map(([Icon, title, text], index) => (
            <Reveal key={title} delay={index * 100}>
              <article className="ytd-meet-method-card"><div><Icon size={20} /></div><h2>{title}</h2><p>{text}</p></article>
            </Reveal>
          ))}
        </div>
      </section>

      <section className="ytd-meet-questions">
      <SectionLabel>Le projet en questions</SectionLabel>
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
      </section>

      <Reveal><SectionLabel>Le fondateur</SectionLabel></Reveal>
      <Reveal><div style={{ display: "grid", gridTemplateColumns: "260px 1fr", gap: 48 }} className="ytd-founder ytd-founder-panel">
        <div className="ytd-founder-portrait" style={{ width: 240, height: 290, background: T.greenDeep, border: `1px solid ${T.line}`, display: "flex", alignItems: "center", justifyContent: "center" }}>
          <NodeMark size={16} color={T.green} />
        </div>
        <div>
          <h3 style={{ fontFamily: "'Newsreader', serif", fontSize: 24, fontWeight: 600, margin: "0 0 16px" }}>Yewtod</h3>
          <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15.5, color: T.inkSoft, lineHeight: 1.7, margin: "0 0 16px" }}>
            J'ai travaillé quelques années entre économie du développement et science des données, dont pas mal de terrain en Afrique de l'Ouest. C'est de là que vient l'obsession pour les systèmes complexes — on ne comprend pas grand-chose en isolant une seule variable.
          </p>
          <p style={{ fontFamily: "'Inter', sans-serif", fontSize: 15.5, color: T.inkSoft, lineHeight: 1.7, margin: "0 0 20px" }}>
            En ce moment, je passe le plus clair de mon temps sur la gouvernance des données et l'usage de l'IA dans les politiques publiques — deux sujets où la théorie et la pratique se parlent encore trop peu.
          </p>
          <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>{["Économie du développement", "Gouvernance des données", "Systèmes complexes", "IA & politiques publiques"].map(v => <Tag key={v}>{v}</Tag>)}</div>
        </div>
      </div></Reveal>
    </div>
  );
}
