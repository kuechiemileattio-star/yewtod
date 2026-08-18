import React from "react";
import { T } from "../theme.js";

export default function Field({ label, children }) {
  return (
    <label style={{ display: "block" }}>
      <span style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, color: T.inkSoft, textTransform: "uppercase", letterSpacing: "0.05em", display: "block", marginBottom: 8 }}>{label}</span>
      {children}
    </label>
  );
}

export const inputStyle = {
  width: "100%", padding: "12px 14px", border: `1px solid ${T.line}`, background: "#fff",
  fontFamily: "'Inter', sans-serif", fontSize: 14.5, color: T.ink, outline: "none", boxSizing: "border-box",
};
