import React from "react";
import { T } from "../../theme.js";

const BAR_WIDTH = 24;
const GAP = 22;
const CHART_HEIGHT = 150;

/** Single-series monthly bar chart — data: [{ label, count }]. */
export default function MonthlyChart({ data }) {
  const max = Math.max(1, ...data.map(d => d.count));
  const width = data.length * (BAR_WIDTH + GAP);

  return (
    <svg width="100%" viewBox={`0 0 ${width} ${CHART_HEIGHT + 26}`} preserveAspectRatio="xMidYMax meet" role="img" aria-label="Publications par mois">
      <line x1={0} y1={CHART_HEIGHT} x2={width} y2={CHART_HEIGHT} stroke={T.line} strokeWidth={1} />
      {data.map((d, i) => {
        const x = i * (BAR_WIDTH + GAP) + GAP / 2;
        const barHeight = d.count > 0 ? Math.max(6, (d.count / max) * (CHART_HEIGHT - 26)) : 0;
        const y = CHART_HEIGHT - barHeight;
        const r = Math.min(4, barHeight);
        return (
          <g key={d.label}>
            {barHeight > 0 && (
              <path
                d={`M ${x} ${CHART_HEIGHT} L ${x} ${y + r} A ${r} ${r} 0 0 1 ${x + r} ${y} L ${x + BAR_WIDTH - r} ${y} A ${r} ${r} 0 0 1 ${x + BAR_WIDTH} ${y + r} L ${x + BAR_WIDTH} ${CHART_HEIGHT} Z`}
                fill={T.green}
              >
                <title>{`${d.label} : ${d.count} publication${d.count > 1 ? "s" : ""}`}</title>
              </path>
            )}
            {d.count > 0 && (
              <text x={x + BAR_WIDTH / 2} y={y - 7} textAnchor="middle" fontSize="11" fontFamily="'JetBrains Mono', monospace" fill={T.ink}>{d.count}</text>
            )}
            <text x={x + BAR_WIDTH / 2} y={CHART_HEIGHT + 18} textAnchor="middle" fontSize="10.5" fontFamily="'Inter', sans-serif" fill={T.inkSoft}>{d.label}</text>
          </g>
        );
      })}
    </svg>
  );
}
