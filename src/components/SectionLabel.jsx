import React from "react";
import { T } from "../theme.js";

export default function SectionLabel({ children }) {
  return (
    <div className="ytd-section-label" style={{ display: "flex", alignItems: "center", gap: 10, marginBottom: 28 }}>
      <span style={{
        fontFamily: "'IBM Plex Mono', monospace", fontSize: 12, letterSpacing: "0.12em",
        textTransform: "uppercase", color: T.inkSoft,
      }}>{children}</span>
    </div>
  );
}
