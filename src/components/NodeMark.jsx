import React from "react";
import { T } from "../theme.js";

/* Petit glyphe "nœud-lien" — signature graphique du site.
   Un nœud vert plein (le travail, la recherche) relié par un
   trait à un nœud rouge creux (le signal, la décision) : deux
   couleurs, une connexion. En variante "pulse" (logo, hero),
   le trait se dessine une fois au chargement puis le nœud
   vert respire doucement. */
export default function NodeMark({ size = 10, color, style, pulse }) {
  const nodeColor = color || T.green;
  return (
    <svg width={size * 2.6} height={size} viewBox="0 0 26 10" style={style} aria-hidden="true" className={pulse ? "ytd-node-pulse" : ""}>
      <circle cx="3" cy="5" r="2.6" fill={nodeColor} />
      <line
        className={pulse ? "ytd-node-line-draw" : ""}
        x1="5.5" y1="5" x2="20.5" y2="5"
        stroke={nodeColor} strokeWidth="1" opacity="0.55"
      />
      <circle cx="23" cy="5" r="2.6" fill="none" stroke={T.red} strokeWidth="1.3" />
    </svg>
  );
}
