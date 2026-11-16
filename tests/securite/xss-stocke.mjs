import { chromium } from 'playwright';
/*
 * XSS stocké de bout en bout : php artisan tinker tests/securite/injecter-charges-xss.php
 * (écrit des charges en base, en contournant la validation), puis :
 *   COOKIE="$(php artisan yewtod:dev-session superadmin@yewtod.test | tail -1)" node tests/securite/xss-stocke.mjs
 * À lancer sur une base de DÉMONSTRATION (php artisan migrate:fresh --seed && db:seed --class=DemoSeeder ensuite).
 */
import { readFileSync } from 'node:fs';
const u = JSON.parse(readFileSync('/tmp/yewtod-xss.json', 'utf8'));
const B = 'http://127.0.0.1:8002';
const pages = [u.article, u.viz, u.episode, u.book, B + '/', B + '/works', B + '/works?q=XSS', B + '/books', B + '/meet', B + '/feed', B + '/une-404',
    B + '/dashboard', B + '/dashboard/contenus/articles', B + '/dashboard/moderation?statut=approved', B + '/dashboard/collaborations', B + '/dashboard/livres', B + '/dashboard/utilisateurs', B + '/dashboard/parametres', B + '/dashboard/journal'];
const b = await chromium.launch({ executablePath: '/usr/bin/google-chrome', args: ['--no-sandbox'] });
const ctx = await b.newContext();
const [n, ...r] = process.env.COOKIE.trim().split('='); await ctx.addCookies([{ name: n, value: r.join('='), domain: '127.0.0.1', path: '/' }]);
const p = await ctx.newPage();
let fired = [];
p.on('dialog', async (d) => { fired.push(`${p.url()} → ${d.message()}`); await d.dismiss(); });
for (const url of pages) {
    await p.goto(url, { waitUntil: 'networkidle' }).catch(() => {});
    // Clique les liens javascript: éventuels (achat, références, réseaux sociaux).
    for (const a of await p.locator('a[href^="javascript:" i]').all()) { fired.push(`lien javascript: présent sur ${url}`); await a.click().catch(() => {}); }
    if (url === B + '/dashboard/collaborations') { await p.getByText(/Ouvrir/).first().click().catch(() => {}); await p.waitForTimeout(800); }
    await p.waitForTimeout(300);
}
await b.close();
console.log(fired.length ? 'EXÉCUTION DÉTECTÉE :\n' + fired.join('\n') : `Aucune exécution de script sur ${pages.length} pages (charges stockées dans tous les champs affichés).`);
