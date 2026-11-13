#!/usr/bin/env node
/*
 * Cross-engine smoke check of key public pages (read-only).
 * Usage: PW_MODULE=<playwright> ENGINE=webkit|chromium node tools/qa/engine-check.cjs <out-dir>
 * Where WebKit's system libraries are missing, point LD_LIBRARY_PATH at user-space copies and set
 * PLAYWRIGHT_SKIP_VALIDATE_HOST_REQUIREMENTS=1.
 */
'use strict';

const fs = require('fs');
const path = require('path');
const pw = require(process.env.PW_MODULE || 'playwright');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8097';
const ENGINE = process.env.ENGINE || 'webkit';
const OUT = path.resolve(process.argv[2] || `engine-${ENGINE}`);
const PAGES = ['/', '/catalog', '/product/hap-ax2', '/services', '/solutions', '/contact', '/en/solutions', '/tk/catalog'];
const VPS = [{ key: '390x844', w: 390, h: 844, mobile: true }, { key: '1440x1000', w: 1440, h: 1000, mobile: false }];
fs.mkdirSync(OUT, { recursive: true });

(async () => {
  const browser = await pw[ENGINE].launch(ENGINE === 'chromium' ? { executablePath: process.env.CHROME || '/usr/bin/google-chrome' } : {});
  const report = { engine: ENGINE, version: browser.version(), base: BASE, generated: new Date().toISOString(), results: [] };
  for (const vp of VPS) {
    const ctx = await browser.newContext({ viewport: { width: vp.w, height: vp.h }, deviceScaleFactor: 2, isMobile: ENGINE !== 'firefox' && vp.mobile, hasTouch: vp.mobile });
    const page = await ctx.newPage();
    for (const url of PAGES) {
      const diag = { console: [], failed: [] };
      const onC = (m) => { if (m.type() === 'error') diag.console.push(m.text().slice(0, 200)); };
      const onR = (r) => { if (r.status() >= 400) diag.failed.push(`${r.status()} ${r.url().replace(BASE, '')}`); };
      const onE = (e) => diag.console.push('pageerror: ' + String(e).slice(0, 200));
      page.on('console', onC); page.on('response', onR); page.on('pageerror', onE);
      const t0 = Date.now();
      const resp = await page.goto(BASE + url, { waitUntil: 'load' });
      const loadMs = Date.now() - t0;
      await page.evaluate(async () => {
        const frame = () => new Promise((r) => requestAnimationFrame(() => requestAnimationFrame(r)));
        const step = Math.floor(innerHeight * 0.5);
        for (let y = 0; y < document.documentElement.scrollHeight; y += step) { scrollTo(0, y); await frame(); await new Promise((r) => setTimeout(r, 100)); }
        await Promise.all([...document.images].map((i) => (i.complete ? null : new Promise((r) => { i.onload = i.onerror = r; setTimeout(r, 5000); }))));
        scrollTo(0, 0); await frame();
      });
      await page.waitForTimeout(1200);
      const m = await page.evaluate(async () => {
        const de = document.documentElement;
        await document.fonts.ready;
        const fonts = [...document.fonts].filter((f) => f.status === 'loaded').map((f) => f.family.replace(/"/g, ''));
        return {
          scrollWidth: de.scrollWidth, clientWidth: de.clientWidth,
          revealHidden: document.querySelectorAll('[data-reveal]:not(.is-visible)').length,
          brokenImages: [...document.images].filter((i) => i.complete && i.naturalWidth === 0).map((i) => i.currentSrc || i.src),
          fontsLoaded: [...new Set(fonts)],
          h1: document.querySelector('h1')?.innerText.trim().slice(0, 60),
        };
      });
      page.off('console', onC); page.off('response', onR); page.off('pageerror', onE);
      const name = `${url === '/' ? 'home' : url.replace(/^\//, '').replace(/\//g, '-')}-${vp.key}.png`;
      if (vp.mobile || url === '/solutions') await page.screenshot({ path: path.join(OUT, name) });
      report.results.push({ url, viewport: vp.key, status: resp.status(), loadMs, overflow: m.scrollWidth > m.clientWidth, ...m, ...diag, screenshot: vp.mobile || url === '/solutions' ? name : null });
      console.log(`${ENGINE} ${vp.key} ${url} ${resp.status()} sw=${m.scrollWidth}/${m.clientWidth} reveal-hidden=${m.revealHidden} broken=${m.brokenImages.length} console=${diag.console.length} failed=${diag.failed.length} fonts=${m.fontsLoaded.join('+')}`);
    }
    await ctx.close();
  }
  await browser.close();
  fs.writeFileSync(path.join(OUT, `${ENGINE}-results.json`), JSON.stringify(report, null, 2));
})().catch((e) => { console.error(e); process.exit(1); });
