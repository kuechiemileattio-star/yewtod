/*
 * Usage (serveur en mode production sur 8002, données de démo) :
 *   APP_ENV=production APP_DEBUG=false APP_URL=http://127.0.0.1:8002 SESSION_SECURE_COOKIE=false php artisan serve --port=8002
 *   node tests/securite/livewire-attaques.mjs
 *
 * Sondes d'escalade via Livewire (audit dynamique) : un membre appelle
 * directement des méthodes ou modifie des propriétés depuis la console.
 */
import { chromium } from 'playwright';
import { execSync } from 'node:child_process';

const BASE = 'http://127.0.0.1:8002';
// Identifiants de démonstration lus directement en base (données du DemoSeeder).
const q = (sql) => execSync(`sudo mariadb -N yewtod -e "${sql}"`).toString().trim();
const contrib = q(`select id from users where email='contributeur@yewtod.test'`);
const ids = {
    own: q(`select id from contents where author_id=${contrib} and status='draft' limit 1`),
    other: q(`select id from contents where author_id<>${contrib} and status='published' and type='articles' limit 1`),
    bookReview: q(`select id from reviews where reviewable_type like '%Book' and status='pending' limit 1`),
    superadmin: q(`select id from users where email='superadmin@yewtod.test'`),
    superRole: q(`select id from roles where slug='super-admin'`),
};
const browser = await chromium.launch({ executablePath: '/usr/bin/google-chrome', args: ['--no-sandbox'] });
const results = [];
const ok = (name, pass, detail = '') => { results.push(pass); console.log((pass ? 'OK    ' : 'ÉCHEC ') + name + (detail ? ' — ' + detail : '')); };
const db = (sql) => execSync(`sudo mariadb -N yewtod -e "${sql}"`).toString().trim();

async function as(email) {
    const cookie = execSync(`php artisan yewtod:dev-session ${email}`).toString().trim().split('\n').pop();
    const ctx = await browser.newContext();
    const [n, ...r] = cookie.split('=');
    await ctx.addCookies([{ name: n, value: r.join('='), domain: '127.0.0.1', path: '/' }]);
    const page = await ctx.newPage();
    page.on('dialog', (d) => d.dismiss());
    return page;
}
// Appel direct sur le premier composant Livewire de la page ; renvoie les statuts HTTP de /update.
async function call(page, js) {
    const statuses = [];
    const listener = (res) => { if (res.url().includes('/update')) statuses.push(res.status()); };
    page.on('response', listener);
    await page.evaluate(async (code) => {
        const c = window.Livewire.all()[0].$wire;
        try { await new Function('c', 'return (async () => {' + code + '})()')(c); } catch (e) { /* erreur attendue */ }
    }, js).catch(() => {});
    await page.waitForTimeout(1200);
    page.off('response', listener);
    return statuses;
}

// --- Contributeur ---
let p = await as('contributeur@yewtod.test');
let r = await p.goto(`${BASE}/dashboard/utilisateurs`);
ok('Contributeur : écran Utilisateurs refusé', r.status() === 403, `HTTP ${r.status()}`);
r = await p.goto(`${BASE}/dashboard/contenus/articles/${ids.other}/modifier`);
ok('Contributeur : édition du contenu publié d\'un autre refusée', r.status() === 403, `HTTP ${r.status()}`);
r = await p.goto(`${BASE}/dashboard/contenus/articles/${ids.own}/modifier`);
let st = await call(p, `await c.$set('contentId', ${ids.other});`);
ok('Contributeur : propriété verrouillée contentId non modifiable', st.some((s) => s >= 400), `HTTP ${st}`);
await p.goto(`${BASE}/dashboard/contenus/articles/${ids.own}/modifier`);
await call(p, `await c.save('publish');`);
ok('Contributeur : publication directe refusée (circuit éditorial)', db(`select status from contents where id=${ids.own}`) === 'draft', db(`select status from contents where id=${ids.own}`));
await p.goto(`${BASE}/dashboard/contenus/articles/${ids.own}/modifier`);
await call(p, `await c.$set('type', 'rapports');`);
ok('Contributeur : propriété verrouillée type non modifiable', db(`select type from contents where id=${ids.own}`) === 'articles');
r = await p.goto(`${BASE}/dashboard/moderation`);
await call(p, `await c.approve(${ids.bookReview});`);
ok('Contributeur : ne peut pas approuver un avis de livre', db(`select status from reviews where id=${ids.bookReview}`) === 'pending');
r = await p.goto(`${BASE}/dashboard/livres`);
ok('Contributeur : écran Livres refusé', r.status() === 403, `HTTP ${r.status()}`);
await p.context().close();

// --- Administrateur (ne touche pas au Super Admin : R5, R6) ---
p = await as('admin@yewtod.test');
await p.goto(`${BASE}/dashboard/utilisateurs`);
const adminId = db(`select id from users where email='admin@yewtod.test'`);
await call(p, `await c.changeRole(${adminId}, ${ids.superRole});`);
ok('Administrateur : ne s\'attribue pas le rôle Super Admin (R1, R5)', db(`select role_id from users where id=${adminId}`) !== String(ids.superRole));
await call(p, `await c.toggleStatus(${ids.superadmin});`);
ok('Administrateur : ne suspend pas le Super Admin (R6)', db(`select status from users where id=${ids.superadmin}`) === 'active');
await call(p, `await c.deleteUser(${ids.superadmin});`);
ok('Administrateur : ne supprime pas le Super Admin (R6)', db(`select count(*) from users where id=${ids.superadmin}`) === '1');
await call(p, `await c.resetTwoFactor(${ids.superadmin});`);
ok('Administrateur : ne réinitialise pas la 2FA du Super Admin', db(`select two_factor_confirmed_at is not null from users where id=${ids.superadmin}`) === '1');
await p.goto(`${BASE}/dashboard/journal`).then((res) => ok('Administrateur : journal d\'audit refusé (Super Admin seul)', res.status() === 403, `HTTP ${res.status()}`));
await p.context().close();

// --- Modérateur des collaborations ---
p = await as('collaborations@yewtod.test');
for (const path of ['/dashboard/contenus/articles', '/dashboard/parametres', '/dashboard/mediatheque', '/dashboard/newsletter/export']) {
    r = await p.goto(BASE + path);
    ok(`Modérateur collaborations : ${path} refusé`, r.status() === 403, `HTTP ${r.status()}`);
}
await p.context().close();

// --- Témoins positifs : le même mécanisme d'appel fonctionne pour une action AUTORISÉE ---
p = await as('editeur@yewtod.test');
await p.goto(`${BASE}/dashboard/profil`);
const before = db(`select name from users where email='editeur@yewtod.test'`);
st = await call(p, `await c.$set('name', 'Témoin Positif'); await c.saveProfile();`);
ok('TÉMOIN : un appel Livewire autorisé aboutit (nom modifié)', db(`select name from users where email='editeur@yewtod.test'`) === 'Témoin Positif', `HTTP ${st}`);
execSync(`sudo mariadb yewtod -e "update users set name='${before}' where email='editeur@yewtod.test'"`);
await p.goto(`${BASE}/dashboard/moderation`);
const articleReview = db(`select id from reviews where reviewable_type like '%Content' and status='pending' limit 1`);
await call(p, `await c.approve(${articleReview});`);
ok('TÉMOIN : un éditeur approuve un avis d\'article', db(`select status from reviews where id=${articleReview}`) === 'approved');
execSync(`sudo mariadb yewtod -e "update reviews set status='pending', moderated_by=null, moderated_at=null where id=${articleReview}"`);

// --- Éditeur : le profil ignore les champs protégés ---
await p.goto(`${BASE}/dashboard/profil`);
await call(p, `await c.$set('email', 'pirate@exemple.fr'); await c.saveProfile();`);
ok('Éditeur : email non modifiable via le profil (R3)', db(`select email from users where email='editeur@yewtod.test'`) === 'editeur@yewtod.test');
await p.context().close();

await browser.close();
console.log(`\n${results.filter(Boolean).length}/${results.length} sondes réussies.`);
