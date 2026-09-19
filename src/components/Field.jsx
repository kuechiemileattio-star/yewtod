import React from "react";
import { T } from "../theme.js";

export default function Field({ label, hint, children, required }) {
  return (
    <label className="ytd-field" style={{ display: "block" }}>
      <span className={`ytd-field-label${required ? " is-required" : ""}`} style={{ fontFamily: "'IBM Plex Mono', monospace", fontSize: 11.5, color: T.inkSoft, textTransform: "uppercase", letterSpacing: "0.05em", display: "block", marginBottom: 8 }}>{label}</span>
      {children}
      {hint && <span style={{ display: "block", marginTop: 6, color: T.inkSoft, fontFamily: "'Inter', sans-serif", fontSize: 11.5 }}>{hint}</span>}
    </label>
  );
}

export const inputStyle = {
  width: "100%", padding: "12px 14px", border: `1px solid ${T.line}`, background: "#fff",
  fontFamily: "'Inter', sans-serif", fontSize: 14.5, color: T.ink, outline: "none", boxSizing: "border-box",
};
