import React from "react";
import { T } from "../theme.js";

export default function Tag({ children }) {
  return (
    <span className="ytd-tag" style={{
      fontFamily: "'IBM Plex Mono', monospace", fontSize: 11, color: T.inkSoft,
      border: `1px solid ${T.line}`, padding: "3px 8px", borderRadius: 20, letterSpacing: "0.02em",
    }}>{children}</span>
  );
}
