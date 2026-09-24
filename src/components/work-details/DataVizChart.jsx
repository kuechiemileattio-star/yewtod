import React, { useEffect, useState } from "react";
import { T } from "../../theme.js";
import { csvToChartData } from "../../lib/csv.js";
import { useInView } from "../Reveal.jsx";

const PALETTE = [T.green, T.red, T.lime, T.greenDeep, "#8a6fd6"];

function useCsvData(url) {
  const [state, setState] = useState({ loading: !!url, error: "", data: null });
  useEffect(() => {
    if (!url) { setState({ loading: false, error: "", data: null }); return; }
    let active = true;
    setState({ loading: true, error: "", data: null });
    fetch(url)
      .then(res => { if (!res.ok) throw new Error("download failed"); return res.text(); })
      .then(text => { if (active) setState({ loading: false, error: "", data: csvToChartData(text) }); })
      .catch(() => { if (active) setState({ loading: false, error: "Impossible de lire le fichier CSV.", data: null }); });
    return () => { active = false; };
  }, [url]);
  return state;
}

function ChartLegend({ series }) {
  if (series.length < 2) return null;
  return (
    <div className="ytd-dataviz-legend">
      {series.map((s, i) => (
        <span key={s.name}><i style={{ background: PALETTE[i % PALETTE.length] }} />{s.name}</span>
      ))}
    </div>
  );
}

function BarChart({ labels, series, animate }) {
  const [hover, setHover] = useState(null);
  const W = 720, H = 340, padL = 46, padB = 36, padT = 16, padR = 12;
  const plotW = W - padL - padR, plotH = H - padT - padB;
  const max = Math.max(1, ...series.flatMap(s => s.values));
  const groupW = plotW / labels.length;
  const barW = Math.min(34, (groupW * 0.7) / series.length);

  return (
    <svg viewBox={`0 0 ${W} ${H}`} className="ytd-dataviz-svg" role="img" aria-label="Graphique en barres">
      {[0, 0.25, 0.5, 0.75, 1].map(f => (
        <g key={f}>
          <line x1={padL} x2={W - padR} y1={padT + plotH * (1 - f)} y2={padT + plotH * (1 - f)} className="ytd-dataviz-gridline" />
          <text x={padL - 8} y={padT + plotH * (1 - f) + 4} className="ytd-dataviz-axislabel" textAnchor="end">{Math.round(max * f)}</text>
        </g>
      ))}
      {labels.map((label, i) => {
        const groupX = padL + i * groupW + (groupW - barW * series.length) / 2;
        return (
          <g key={label}>
            {series.map((s, si) => {
              const v = s.values[i];
              const h = (v / max) * plotH;
              const x = groupX + si * barW;
              const isHover = hover?.i === i && hover?.si === si;
              return (
                <g key={s.name} onMouseEnter={() => setHover({ i, si })} onMouseLeave={() => setHover(null)}>
                  <rect
                    x={x} width={barW - 3}
                    y={animate ? padT + plotH - h : padT + plotH}
                    height={animate ? h : 0}
                    rx={3}
                    fill={PALETTE[si % PALETTE.length]}
                    opacity={isHover ? 1 : 0.88}
                    style={{ transition: `y .8s cubic-bezier(.16,1,.3,1) ${i * 0.05}s, height .8s cubic-bezier(.16,1,.3,1) ${i * 0.05}s, opacity .15s ease` }}
                  />
                  {isHover && (
                    <text x={x + (barW - 3) / 2} y={padT + plotH - h - 8} textAnchor="middle" className="ytd-dataviz-value">{v}</text>
                  )}
                </g>
              );
            })}
            <text x={groupX + (barW * series.length) / 2} y={H - padB + 18} textAnchor="middle" className="ytd-dataviz-axislabel">{label}</text>
          </g>
        );
      })}
    </svg>
  );
}

function LineChart({ labels, series, animate }) {
  const [hover, setHover] = useState(null);
  const W = 720, H = 340, padL = 46, padB = 36, padT = 16, padR = 16;
  const plotW = W - padL - padR, plotH = H - padT - padB;
  const max = Math.max(1, ...series.flatMap(s => s.values));
  const stepX = labels.length > 1 ? plotW / (labels.length - 1) : 0;
  const pointsFor = s => s.values.map((v, i) => [padL + i * stepX, padT + plotH - (v / max) * plotH]);

  return (
    <svg viewBox={`0 0 ${W} ${H}`} className="ytd-dataviz-svg" role="img" aria-label="Graphique en lignes">
      {[0, 0.25, 0.5, 0.75, 1].map(f => (
        <g key={f}>
          <line x1={padL} x2={W - padR} y1={padT + plotH * (1 - f)} y2={padT + plotH * (1 - f)} className="ytd-dataviz-gridline" />
          <text x={padL - 8} y={padT + plotH * (1 - f) + 4} className="ytd-dataviz-axislabel" textAnchor="end">{Math.round(max * f)}</text>
        </g>
      ))}
      {series.map((s, si) => {
        const pts = pointsFor(s);
        const d = pts.map(([x, y], i) => `${i === 0 ? "M" : "L"}${x},${y}`).join(" ");
        return (
          <g key={s.name}>
            <path
              d={d} fill="none" stroke={PALETTE[si % PALETTE.length]} strokeWidth={2.5} strokeLinecap="round" strokeLinejoin="round"
              pathLength={1}
              style={{
                strokeDasharray: 1,
                strokeDashoffset: animate ? 0 : 1,
                transition: `stroke-dashoffset 1.1s cubic-bezier(.16,1,.3,1) ${si * 0.15}s`,
              }}
            />
            {pts.map(([x, y], i) => {
              const isHover = hover?.i === i && hover?.si === si;
              return (
                <g key={i} onMouseEnter={() => setHover({ i, si })} onMouseLeave={() => setHover(null)}>
                  <circle cx={x} cy={y} r={isHover ? 5 : 3.5} fill={PALETTE[si % PALETTE.length]}
                    opacity={animate ? 1 : 0}
                    style={{ transition: `opacity .4s ease ${0.6 + i * 0.03}s, r .15s ease` }} />
                  {isHover && <text x={x} y={y - 12} textAnchor="middle" className="ytd-dataviz-value">{s.values[i]}</text>}
                </g>
              );
            })}
          </g>
        );
      })}
      {labels.map((label, i) => (
        <text key={label} x={padL + i * stepX} y={H - padB + 18} textAnchor="middle" className="ytd-dataviz-axislabel">{label}</text>
      ))}
    </svg>
  );
}

function AreaChart({ labels, series, animate }) {
  const [hover, setHover] = useState(null);
  const W = 720, H = 340, padL = 46, padB = 36, padT = 16, padR = 16;
  const plotW = W - padL - padR, plotH = H - padT - padB;
  const max = Math.max(1, ...series.flatMap(s => s.values));
  const stepX = labels.length > 1 ? plotW / (labels.length - 1) : 0;
  const pointsFor = s => s.values.map((v, i) => [padL + i * stepX, padT + plotH - (v / max) * plotH]);
  const baseline = padT + plotH;

  return (
    <svg viewBox={`0 0 ${W} ${H}`} className="ytd-dataviz-svg" role="img" aria-label="Graphique en aires">
      {[0, 0.25, 0.5, 0.75, 1].map(f => (
        <g key={f}>
          <line x1={padL} x2={W - padR} y1={padT + plotH * (1 - f)} y2={padT + plotH * (1 - f)} className="ytd-dataviz-gridline" />
          <text x={padL - 8} y={padT + plotH * (1 - f) + 4} className="ytd-dataviz-axislabel" textAnchor="end">{Math.round(max * f)}</text>
        </g>
      ))}
      {series.map((s, si) => {
        const pts = pointsFor(s);
        const line = pts.map(([x, y], i) => `${i === 0 ? "M" : "L"}${x},${y}`).join(" ");
        const area = `${line} L${pts[pts.length - 1][0]},${baseline} L${pts[0][0]},${baseline} Z`;
        const color = PALETTE[si % PALETTE.length];
        return (
          <g key={s.name} style={{ opacity: animate ? 1 : 0, transition: `opacity .9s ease ${si * 0.15}s` }}>
            <path d={area} fill={color} opacity={0.16} />
            <path d={line} fill="none" stroke={color} strokeWidth={2.5} strokeLinecap="round" strokeLinejoin="round" />
            {pts.map(([x, y], i) => {
              const isHover = hover?.i === i && hover?.si === si;
              return (
                <g key={i} onMouseEnter={() => setHover({ i, si })} onMouseLeave={() => setHover(null)}>
                  <circle cx={x} cy={y} r={isHover ? 5 : 3.5} fill={color} />
                  {isHover && <text x={x} y={y - 12} textAnchor="middle" className="ytd-dataviz-value">{s.values[i]}</text>}
                </g>
              );
            })}
          </g>
        );
      })}
      {labels.map((label, i) => (
        <text key={label} x={padL + i * stepX} y={H - padB + 18} textAnchor="middle" className="ytd-dataviz-axislabel">{label}</text>
      ))}
    </svg>
  );
}

function StackedBarChart({ labels, series, animate }) {
  const [hover, setHover] = useState(null);
  const W = 720, H = 340, padL = 46, padB = 36, padT = 16, padR = 12;
  const plotW = W - padL - padR, plotH = H - padT - padB;
  const totals = labels.map((_, i) => series.reduce((sum, s) => sum + s.values[i], 0));
  const max = Math.max(1, ...totals);
  const groupW = plotW / labels.length;
  const barW = Math.min(64, groupW * 0.55);

  return (
    <svg viewBox={`0 0 ${W} ${H}`} className="ytd-dataviz-svg" role="img" aria-label="Graphique en barres empilées">
      {[0, 0.25, 0.5, 0.75, 1].map(f => (
        <g key={f}>
          <line x1={padL} x2={W - padR} y1={padT + plotH * (1 - f)} y2={padT + plotH * (1 - f)} className="ytd-dataviz-gridline" />
          <text x={padL - 8} y={padT + plotH * (1 - f) + 4} className="ytd-dataviz-axislabel" textAnchor="end">{Math.round(max * f)}</text>
        </g>
      ))}
      {labels.map((label, i) => {
        const x = padL + i * groupW + (groupW - barW) / 2;
        let stackedSoFar = 0;
        return (
          <g key={label}>
            {series.map((s, si) => {
              const v = s.values[i];
              const h = (v / max) * plotH;
              const yTop = padT + plotH - (stackedSoFar + v) / max * plotH;
              stackedSoFar += v;
              const isHover = hover?.i === i && hover?.si === si;
              return (
                <g key={s.name} onMouseEnter={() => setHover({ i, si })} onMouseLeave={() => setHover(null)}>
                  <rect
                    x={x} width={barW}
                    y={animate ? yTop : padT + plotH}
                    height={animate ? h : 0}
                    fill={PALETTE[si % PALETTE.length]}
                    opacity={isHover ? 1 : 0.9}
                    style={{ transition: `y .8s cubic-bezier(.16,1,.3,1) ${i * 0.05}s, height .8s cubic-bezier(.16,1,.3,1) ${i * 0.05}s, opacity .15s ease` }}
                  />
                  {isHover && <text x={x + barW / 2} y={yTop - 8} textAnchor="middle" className="ytd-dataviz-value">{v}</text>}
                </g>
              );
            })}
            <text x={x + barW / 2} y={H - padB + 18} textAnchor="middle" className="ytd-dataviz-axislabel">{label}</text>
          </g>
        );
      })}
    </svg>
  );
}

function ScatterChart({ labels, series }) {
  const [hover, setHover] = useState(null);
  const W = 720, H = 360, padL = 50, padB = 40, padT = 16, padR = 20;
  const plotW = W - padL - padR, plotH = H - padT - padB;
  const xs = series[0]?.values || [];
  const ys = series[1]?.values || xs;
  const xMax = Math.max(1, ...xs), xMin = Math.min(0, ...xs);
  const yMax = Math.max(1, ...ys), yMin = Math.min(0, ...ys);
  const px = x => padL + ((x - xMin) / (xMax - xMin || 1)) * plotW;
  const py = y => padT + plotH - ((y - yMin) / (yMax - yMin || 1)) * plotH;

  return (
    <svg viewBox={`0 0 ${W} ${H}`} className="ytd-dataviz-svg" role="img" aria-label="Nuage de points">
      {[0, 0.25, 0.5, 0.75, 1].map(f => (
        <line key={f} x1={padL} x2={W - padR} y1={padT + plotH * (1 - f)} y2={padT + plotH * (1 - f)} className="ytd-dataviz-gridline" />
      ))}
      <line x1={padL} x2={padL} y1={padT} y2={padT + plotH} className="ytd-dataviz-gridline" />
      <line x1={padL} x2={W - padR} y1={padT + plotH} y2={padT + plotH} className="ytd-dataviz-gridline" />
      <text x={padL} y={padT + plotH + 18} textAnchor="start" className="ytd-dataviz-axislabel">{xMin}</text>
      <text x={W - padR} y={padT + plotH + 18} textAnchor="end" className="ytd-dataviz-axislabel">{xMax}</text>
      <text x={padL} y={padT + plotH + 34} textAnchor="start" className="ytd-dataviz-axislabel">{series[0]?.name}</text>
      <text x={padL - 8} y={padT + plotH + 4} textAnchor="end" className="ytd-dataviz-axislabel">{yMin}</text>
      <text x={padL - 8} y={padT + 8} textAnchor="end" className="ytd-dataviz-axislabel">{yMax}</text>
      <text x={padL - 8} y={padT - 6} textAnchor="end" className="ytd-dataviz-axislabel">{series[1]?.name || ""}</text>
      {xs.map((x, i) => {
        const isHover = hover === i;
        return (
          <g key={i} onMouseEnter={() => setHover(i)} onMouseLeave={() => setHover(null)}>
            <circle cx={px(x)} cy={py(ys[i])} r={isHover ? 7 : 5} fill={T.green} opacity={isHover ? 1 : 0.72} style={{ transition: "r .15s ease, opacity .15s ease" }} />
            {isHover && (
              <text x={px(x)} y={py(ys[i]) - 12} textAnchor="middle" className="ytd-dataviz-value">
                {labels[i] ? `${labels[i]} · ` : ""}{x}, {ys[i]}
              </text>
            )}
          </g>
        );
      })}
    </svg>
  );
}

function PieChart({ labels, series, animate }) {
  const [hover, setHover] = useState(null);
  const values = series[0]?.values || [];
  const total = values.reduce((a, b) => a + b, 0) || 1;
  const size = 260, r = 88, cx = size / 2, cy = size / 2;
  const circumference = 2 * Math.PI * r;
  let offsetAcc = 0;

  return (
    <div className="ytd-dataviz-pie-wrap">
      <svg viewBox={`0 0 ${size} ${size}`} className="ytd-dataviz-svg ytd-dataviz-pie" role="img" aria-label="Graphique circulaire">
        <circle cx={cx} cy={cy} r={r} fill="none" stroke={T.line} strokeWidth={30} />
        {values.map((v, i) => {
          const frac = v / total;
          const dash = frac * circumference;
          const gap = circumference - dash;
          const rotation = (offsetAcc / total) * 360 - 90;
          offsetAcc += v;
          const isHover = hover === i;
          return (
            <circle
              key={labels[i]}
              cx={cx} cy={cy} r={r} fill="none"
              stroke={PALETTE[i % PALETTE.length]}
              strokeWidth={isHover ? 34 : 30}
              strokeDasharray={`${dash} ${gap}`}
              strokeDashoffset={animate ? 0 : circumference}
              transform={`rotate(${rotation} ${cx} ${cy})`}
              onMouseEnter={() => setHover(i)}
              onMouseLeave={() => setHover(null)}
              style={{ transition: `stroke-dashoffset 1s cubic-bezier(.16,1,.3,1) ${i * 0.1}s, stroke-width .15s ease`, cursor: "pointer" }}
            />
          );
        })}
        <text x={cx} y={cy - 4} textAnchor="middle" className="ytd-dataviz-pie-center-value">
          {hover != null ? values[hover] : total}
        </text>
        <text x={cx} y={cy + 16} textAnchor="middle" className="ytd-dataviz-pie-center-label">
          {hover != null ? labels[hover] : "Total"}
        </text>
      </svg>
      <ul className="ytd-dataviz-pie-list">
        {labels.map((label, i) => (
          <li key={label} className={hover === i ? "is-active" : ""} onMouseEnter={() => setHover(i)} onMouseLeave={() => setHover(null)}>
            <i style={{ background: PALETTE[i % PALETTE.length] }} />
            <span>{label}</span>
            <strong>{values[i]}</strong>
          </li>
        ))}
      </ul>
    </div>
  );
}

/** Renders a chart from a CSV file's URL, auto-parsed client-side — no
 * hand-authored chart configuration needed. `type` picks the renderer;
 * unrecognised/missing data falls back to a plain message rather than a
 * broken chart. */
export default function DataVizChart({ csvUrl, type = "bar" }) {
  const { loading, error, data } = useCsvData(csvUrl);
  // The ref must be attached from the very first render (even while still
  // loading) — useInView's observer is set up in a mount-only effect, so if
  // the ref only gets attached once data arrives (a later render), that
  // effect has already run against an empty ref and never re-attaches.
  const [ref, inView] = useInView(0.3);

  if (!csvUrl) return null;

  const hasData = data && data.labels.length > 0 && data.series.length > 0;
  const CHART_COMPONENTS = { line: LineChart, pie: PieChart, area: AreaChart, stackedBar: StackedBarChart, scatter: ScatterChart, bar: BarChart };
  const Chart = CHART_COMPONENTS[type] || BarChart;

  return (
    <div ref={ref} className="ytd-dataviz-frame">
      {loading && <div className="ytd-dataviz-status">Lecture du fichier de données…</div>}
      {!loading && error && <div className="ytd-dataviz-status">{error}</div>}
      {!loading && !error && !hasData && <div className="ytd-dataviz-status">Le fichier CSV ne contient pas de données exploitables.</div>}
      {!loading && !error && hasData && (
        <>
          <Chart labels={data.labels} series={data.series} animate={inView} />
          {type !== "scatter" && <ChartLegend series={data.series} />}
        </>
      )}
    </div>
  );
}
