/** Minimal CSV parser — handles quoted fields (with embedded commas/newlines)
 * and escaped quotes (""), which covers real-world exports from Excel/Sheets
 * without pulling in a full CSV library dependency. */
export function parseCsv(text) {
  const rows = [];
  let row = [];
  let field = "";
  let inQuotes = false;
  const pushField = () => { row.push(field); field = ""; };
  const pushRow = () => { pushField(); rows.push(row); row = []; };

  for (let i = 0; i < text.length; i++) {
    const c = text[i];
    if (inQuotes) {
      if (c === '"') {
        if (text[i + 1] === '"') { field += '"'; i++; }
        else inQuotes = false;
      } else {
        field += c;
      }
      continue;
    }
    if (c === '"') { inQuotes = true; continue; }
    if (c === ",") { pushField(); continue; }
    if (c === "\n") { pushRow(); continue; }
    if (c === "\r") continue;
    field += c;
  }
  if (field.length > 0 || row.length > 0) pushRow();

  return rows.filter(r => r.some(cell => cell.trim() !== ""));
}

/** Turns raw CSV rows into chart-ready data: the first column is treated as
 * the category label, every other numeric column becomes its own series
 * (named after its header) — this covers the common case of one or several
 * value columns per category without requiring any manual configuration. */
export function csvToChartData(text) {
  const rows = parseCsv(text);
  if (rows.length < 2) return { labels: [], series: [] };
  const [header, ...body] = rows;
  const seriesNames = header.slice(1);
  const labels = body.map(r => r[0]);
  const series = seriesNames.map((name, colIndex) => ({
    name,
    values: body.map(r => {
      let raw = (r[colIndex + 1] || "").replace(/\s/g, "");
      // A comma is ambiguous: "1,450" (thousands) vs "3,5" (French decimal).
      // Strip thousands-grouping commas (digit + comma + exactly 3 digits)
      // first, then treat any comma left over as a decimal separator.
      raw = raw.replace(/(\d),(?=\d{3}(\D|$))/g, "$1").replace(",", ".");
      const n = parseFloat(raw);
      return Number.isFinite(n) ? n : 0;
    }),
  })).filter(s => s.values.some(v => v !== 0));
  return { labels, series };
}
