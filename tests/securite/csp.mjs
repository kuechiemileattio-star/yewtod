/* Parcours de toutes les pages (et interactions courantes) en enregistrant les violations de CSP. */
import { chromium } from 'playwright';
import { readFileSync } from 'node:fs';
const BASE = process.env.BASE ?? 'http://127.0.0.1:8002';
const pages = JSON.parse(readFileSync(new URL('../visual/pages.json', import.meta.url)));
const b = await chromium.launch({ executablePath: '/usr/bin/google-chrome', args: ['--no-sandbox'] });
const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } });
const [n, ...r] = (process.env.COOKIE ?? '').trim().split('=');
if (n) await ctx.addCookies([{ name: n, value: r.join('='), domain: '127.0.0.1', path: '/' }]);
await ctx.addInitScript(() => {
    window.__csp = [];
    document.addEventListener('securitypolicyviolation', (e) => window.__csp.push(`${e.violatedDirective} ${e.blockedURI} ${e.sample || ''}`.trim()));
});
const page = await ctx.newPage();
let total = 0;
for (const p of pages) {
    await page.goto(BASE + p.path, { waitUntil: 'networkidle' });
    await page.mouse.wheel(0, 1500);
    if (p.name === 'connexion' || p.name.includes('profil-mot-de-passe')) await page.locator('input[type=password]').first().fill('Essai-123!abc').catch(() => {});
    if (p.name === 'accueil') { await page.keyboard.press('Control+k'); await page.keyboard.type('rap'); await page.waitForTimeout(600); }
    if (p.name.startsWith('dashboard-livres')) await page.getByText('Nouveau livre').click().catch(() => {});
    await page.waitForTimeout(500);
    const v = await page.evaluate(() => window.__csp);
    total += v.length;
    if (v.length) console.log(p.name, [...new Set(v)].slice(0, 4));
}
await b.close();
console.log(`${total} violation(s) de CSP.`);
