import React from "react";
import { T } from "../theme.js";

export default function Btn({ children, variant = "solid", onClick, style, type = "button" }) {
  const base = {
    fontFamily: "'Inter', sans-serif", fontSize: 14, fontWeight: 600, letterSpacing: "0.01em",
    padding: "12px 22px", borderRadius: 2, cursor: "pointer", display: "inline-flex",
    alignItems: "center", gap: 8, transition: "all 0.15s ease", border: `1px solid ${T.ink}`,
  };
  const variants = {
    solid: { background: T.ink, color: T.paper },
    outline: { background: "transparent", color: T.ink },
    green: { background: T.green, color: "#fff", border: `1px solid ${T.green}` },
  };
  return (
    <button type={type} onClick={onClick} className={`ytd-btn ytd-btn-${variant}`} style={{ ...base, ...variants[variant], ...style }}>
      <span className="ytd-btn-shine" aria-hidden="true" />
      {children}
    </button>
  );
}
