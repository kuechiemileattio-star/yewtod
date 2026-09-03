import React from "react";
import { Link } from "react-router-dom";
import { ChevronRight } from "lucide-react";
import { T } from "../theme.js";

export default function Breadcrumb({ items }) {
  return (
    <nav aria-label="Fil d'Ariane" style={{ display: "flex", alignItems: "center", flexWrap: "wrap", gap: 6, marginBottom: 28, fontFamily: "'JetBrains Mono', monospace", fontSize: 11.5, color: T.inkSoft }}>
      {items.map((item, i) => {
        const isLast = i === items.length - 1;
        return (
          <span key={item.label} style={{ display: "inline-flex", alignItems: "center", gap: 6, minWidth: 0 }}>
            {i > 0 && <ChevronRight size={12} style={{ flexShrink: 0 }} />}
            {isLast || !item.to ? (
              <span style={{ color: isLast ? T.ink : T.inkSoft, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap", maxWidth: 260 }}>{item.label}</span>
            ) : (
              <Link to={item.to} style={{ color: T.inkSoft, textDecoration: "none" }} onMouseEnter={e => { e.currentTarget.style.color = T.green; }} onMouseLeave={e => { e.currentTarget.style.color = T.inkSoft; }}>{item.label}</Link>
            )}
          </span>
        );
      })}
    </nav>
  );
}
