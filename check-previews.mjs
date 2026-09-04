import { chromium } from "playwright";
const dir = "C:/Users/ATTIOGBE/AppData/Local/Temp/claude/c--Users-ATTIOGBE-Desktop-Dossier-ALITCHA-yewtod-ss/7fca2309-e9d2-4659-b64f-dea686779bbb/scratchpad/audit";
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
const logs = [];
page.on("console", m => { if (m.type() === "error") logs.push(`[console] ${m.text()}`); });
page.on("pageerror", e => logs.push(`[pageerror] ${e.message}`));

await page.goto("http://localhost:5173/login", { waitUntil: "networkidle" });
await page.fill('input[type="email"]', "attiogbeemile315@gmail.com");
await page.fill('input[type="password"]', "Yewtod-QA-2026!Verify");
await page.click('button[type="submit"]');
try {
  await page.waitForURL("**/dashboard", { timeout: 10000 });
} catch (e) {
  const bodyText = await page.locator("body").innerText().catch(() => "");
  console.log("LOGIN_FAILED. current url=" + page.url());
  console.log("BODY: " + bodyText.slice(0, 300));
  process.exit(1);
}

// Books preview
await page.goto("http://localhost:5173/dashboard/books", { waitUntil: "networkidle" });
await page.waitForTimeout(600);
await page.click(".ytd-admin-book-card");
await page.waitForTimeout(500);
await page.screenshot({ path: `${dir}/dashboard-book-preview.png` });
logs.push("book preview modal visible: " + await page.locator(".ytd-admin-book-preview").isVisible());
await page.keyboard.press("Escape").catch(() => {});
await page.click(".ytd-admin-book-preview-head button").catch(() => {});

// Publications preview
await page.goto("http://localhost:5173/dashboard/publications", { waitUntil: "networkidle" });
await page.waitForTimeout(600);
await page.click(".ytd-admin-publication-cover");
await page.waitForTimeout(500);
await page.screenshot({ path: `${dir}/dashboard-work-preview.png`, fullPage: true });
logs.push("work preview modal visible: " + await page.locator(".ytd-publication-preview-modal").isVisible());

console.log(logs.join("\n"));
await browser.close();
