/*
 * Audit d'accessibilité automatique (cahier § 9.4, § 10 P1, WCAG 2.1 AA)
 * avec axe-core sur toutes les pages de tests/visual/pages.json, en clair et
 * en sombre. Usage : COOKIE="$(php artisan yewtod:dev-session admin@yewtod.test)" node tests/visual/a11y.mjs
 * Résultat : docs/captures/accessibilite.md
 */
import { chromium } from 'playwright';
import { readFileSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const axeSource = readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const BASE = process.env.BASE ?? 'http://127.0.0.1:8000';
const COOKIE = process.env.COOKIE ?? '';
const pages = JSON.parse(readFileSync(new URL('./pages.json', import.meta.url)));
const browser = await chromium.launch({ executablePath: process.env.CHROME ?? '/usr/bin/google-chrome', args: ['--no-sandbox'] });
const rows = [];
let total = 0;
for (const theme of ['light', 'dark']) {
    // bypassCSP : la CSP du site bloque (à raison) le script d'audit injecté.
    const context = await browser.newContext({ colorScheme: theme, reducedMotion: 'reduce', viewport: { width: 1280, height: 900 }, bypassCSP: true });
    const host = new URL(BASE).hostname;
    const cookies = [{ name: 'theme', value: 'system', domain: host, path: '/' }];
    if (COOKIE) { const [n, ...r] = COOKIE.trim().split('='); cookies.push({ name: n, value: r.join('='), domain: host, path: '/' }); }
    await context.addCookies(cookies);
    const page = await context.newPage();
    for (const p of pages.filter((p) => !p.auth || COOKIE)) {
        await page.goto(BASE + p.path, { waitUntil: 'networkidle' });
        await page.addScriptTag({ content: axeSource });
        const result = await page.evaluate(async () => await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] }, resultTypes: ['violations'] }));
        const bad = result.violations.filter((v) => ['serious', 'critical'].includes(v.impact));
        total += bad.length;
        rows.push(`| ${p.name} | ${theme === 'dark' ? 'sombre' : 'clair'} | ${bad.length ? bad.map((v) => `${v.id} (${v.nodes.length})`).join(', ') : '✅ aucune'} |`);
        for (const v of bad) console.log(p.name, theme, v.id, v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(' | '));
    }
    await context.close();
}
await browser.close();
writeFileSync(new URL('../../docs/captures/accessibilite.md', import.meta.url), ['# Audit d\'accessibilité (axe-core, WCAG 2.1 A/AA)', '', `Mis à jour le ${new Date().toISOString()}. Seules les violations « sérieuses » et « critiques » sont listées.`, '', '| Page | Thème | Violations |', '| --- | --- | --- |', ...rows].join('\n') + '\n');
console.log(`${total} violation(s) sérieuse(s) ou critique(s).`);
