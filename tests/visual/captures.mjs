/*
 * Revue responsive automatique (décision 14) : chaque page est capturée à
 * 320, 375, 768, 1024 et 1440 px, en clair et en sombre, et on vérifie
 * qu'aucune ne défile horizontalement et que les cibles tactiles font 44 px.
 *
 * Usage (serveur local lancé, données de démo chargées) :
 *   COOKIE="$(php artisan yewtod:dev-session admin@yewtod.test)" \
 *   BASE=http://127.0.0.1:8000 node tests/visual/captures.mjs [filtre]
 *
 * Résultat : docs/captures/*.jpg + docs/captures/rapport.md
 */
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync, readFileSync } from 'node:fs';

const BASE = process.env.BASE ?? 'http://127.0.0.1:8000';
const COOKIE = process.env.COOKIE ?? '';
const filter = process.argv[2] ?? '';
const WIDTHS = [320, 375, 768, 1024, 1440];
const THEMES = ['light', 'dark'];
const pages = JSON.parse(readFileSync(new URL('./pages.json', import.meta.url)));
const out = new URL('../../docs/captures/', import.meta.url).pathname;
mkdirSync(out, { recursive: true });

const browser = await chromium.launch({ executablePath: process.env.CHROME ?? '/usr/bin/google-chrome', args: ['--no-sandbox'] });
const report = [];

for (const p of pages.filter((p) => p.name.includes(filter))) {
    for (const theme of THEMES) {
        const context = await browser.newContext({ colorScheme: theme, reducedMotion: 'reduce' });
        const host = new URL(BASE).hostname;
        const cookies = [{ name: 'theme', value: 'system', domain: host, path: '/' }];
        if (p.auth && COOKIE) {
            const [name, ...rest] = COOKIE.trim().split('=');
            cookies.push({ name, value: rest.join('='), domain: host, path: '/' });
        }
        await context.addCookies(cookies);
        const page = await context.newPage();
        for (const width of WIDTHS) {
            await page.setViewportSize({ width, height: 900 });
            const res = await page.goto(BASE + p.path, { waitUntil: 'networkidle' });
            const metrics = await page.evaluate(() => {
                const doc = document.documentElement;
                const small = [...document.querySelectorAll('a, button, input:not([type=hidden]), select, textarea, [role=switch]')]
                    .filter((el) => {
                        const r = el.getBoundingClientRect();
                        const cs = getComputedStyle(el);
                        if (r.width <= 2 || r.height <= 2 || cs.visibility === 'hidden') return false;
                        if (el.closest('p, li > span, .prose')) return false; // liens dans un texte : exemptés (WCAG 2.5.8)
                        return r.height < 43.5 && !(el.type === 'checkbox' || el.type === 'radio');
                    })
                    .slice(0, 5)
                    .map((el) => (el.textContent || el.getAttribute('aria-label') || el.name || el.tagName).trim().slice(0, 40));
                return { overflow: doc.scrollWidth > window.innerWidth + 1, scrollWidth: doc.scrollWidth, small };
            });
            const file = `${p.name}-${width}-${theme === 'dark' ? 'sombre' : 'clair'}.jpg`;
            await page.screenshot({ path: out + file, fullPage: true, type: 'jpeg', quality: 55 });
            report.push({ page: p.name, width, theme, status: res?.status(), ...metrics });
        }
        await context.close();
    }
}
await browser.close();

const lines = ['# Revue responsive automatique', '', `Générée le ${new Date().toISOString()}.`, '',
    '| Page | Largeur | Thème | HTTP | Défilement horizontal | Cibles < 44 px (hors liens dans le texte) |', '| --- | --- | --- | --- | --- | --- |'];
for (const r of report) {
    lines.push(`| ${r.page} | ${r.width} | ${r.theme === 'dark' ? 'sombre' : 'clair'} | ${r.status} | ${r.overflow ? '❌ ' + r.scrollWidth + ' px' : '✅ aucun'} | ${r.small.length ? r.small.join(', ') : '✅'} |`);
}
writeFileSync(out + 'rapport.md', lines.join('\n') + '\n');
const bad = report.filter((r) => r.overflow || r.status >= 400);
console.log(`${report.length} captures, ${bad.length} problème(s) de débordement ou d'erreur HTTP.`);
for (const b of bad) console.log(' -', b.page, b.width, b.theme, b.status, b.scrollWidth);
