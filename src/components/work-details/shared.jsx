import React from "react";
import { ArrowUpRight, Download } from "lucide-react";
import { T } from "../../theme.js";

/** A titled prose block — the workhorse of every detail layout. */
export function Section({ title, children, tight }) {
  if (!children) return null;
  return (
    <section className="ytd-work-detail-section" style={tight ? { marginTop: 24 } : undefined}>
      <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 24, fontWeight: 500, margin: "0 0 10px", color: T.greenDeep }}>{title}</h2>
      <div style={{ color: T.ink, fontSize: 17, lineHeight: 1.7, whiteSpace: "pre-line" }}>{children}</div>
    </section>
  );
}

/** Renders a newline-joined list field ("a\nb\nc") as a real list. Returns null when empty. */
export function FieldList({ title, value, ordered }) {
  const items = (value || "").split("\n").map(s => s.trim()).filter(Boolean);
  if (!items.length) return null;
  const Tag = ordered ? "ol" : "ul";
  return (
    <section className="ytd-work-detail-section">
      <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 24, fontWeight: 500, margin: "0 0 10px", color: T.greenDeep }}>{title}</h2>
      <Tag style={{ margin: 0, paddingLeft: 22, color: T.ink, fontSize: 16, lineHeight: 1.8 }}>
        {items.map((item, i) => <li key={i}>{item}</li>)}
      </Tag>
    </section>
  );
}

/** A single pulled quote, styled like an editorial callout. */
export function PullQuote({ text }) {
  if (!text) return null;
  return (
    <blockquote style={{ margin: "34px 0", padding: "22px 28px", borderLeft: `3px solid ${T.red}`, background: T.paperAlt, fontFamily: "'Fraunces', serif", fontStyle: "italic", fontSize: 21, lineHeight: 1.5, color: T.ink }}>
      {text}
    </blockquote>
  );
}

/** Multiple quotes (newline-joined) rendered as a stack of pulled quotes. */
export function QuoteStack({ value }) {
  const quotes = (value || "").split("\n").map(s => s.trim()).filter(Boolean);
  if (!quotes.length) return null;
  return <>{quotes.map((q, i) => <PullQuote key={i} text={q} />)}</>;
}

/** Small uppercase mono badge — status, difficulty, category. */
export function Pill({ tone = T.green, children }) {
  if (!children) return null;
  return (
    <span style={{ display: "inline-flex", alignItems: "center", gap: 6, padding: "5px 10px", border: `1px solid ${tone}55`, background: `${tone}14`, color: tone, borderRadius: 20, fontFamily: "'JetBrains Mono', monospace", fontSize: 11, textTransform: "uppercase", letterSpacing: "0.04em" }}>
      {children}
    </span>
  );
}

/** External link styled as a button (download, source code, purchase...). */
export function LinkAction({ href, children, download }) {
  if (!href) return null;
  const Icon = download ? Download : ArrowUpRight;
  return (
    <a href={href} target="_blank" rel="noreferrer" style={{ display: "inline-flex", alignItems: "center", gap: 8, padding: "11px 16px", border: `1px solid ${T.ink}`, background: T.ink, color: T.paper, textDecoration: "none", fontFamily: "'Inter', sans-serif", fontSize: 13.5, fontWeight: 600, borderRadius: 2 }}>
      {children} <Icon size={14} />
    </a>
  );
}

/** A row of key/value facts (version, authors, data source...). */
export function FactRow({ facts }) {
  const visible = facts.filter(([, value]) => value);
  if (!visible.length) return null;
  return (
    <div style={{ display: "flex", flexWrap: "wrap", gap: "10px 28px", padding: "18px 0", borderTop: `1px solid ${T.line}`, borderBottom: `1px solid ${T.line}`, margin: "30px 0" }}>
      {visible.map(([label, value]) => (
        <div key={label}>
          <span style={{ display: "block", fontFamily: "'JetBrains Mono', monospace", fontSize: 10, color: T.inkSoft, textTransform: "uppercase", letterSpacing: "0.06em" }}>{label}</span>
          <strong style={{ fontFamily: "'Plus Jakarta Sans', sans-serif", fontSize: 14, color: T.ink }}>{value}</strong>
        </div>
      ))}
    </div>
  );
}
