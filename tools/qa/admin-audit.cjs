#!/usr/bin/env node
/*
 * Audit of every admin (Filament) page in a real browser: server errors, JavaScript errors, broken images
 * (table thumbnails, photo upload previews), URLs that point to another host, and page-level overflow on a phone.
 *
 *   PW_MODULE=<playwright> BASE=http://localhost:8096 AUTH_STATE=<storageState.json> KEYS=<keys.json> \
 *     node tools/qa/admin-audit.cjs <out-dir>
 *
 * KEYS lists route keys: {"products": [...], "categories": [...], "services": [...], "inquiries": [...]}.
 * Log in on the same host as BASE before running (session cookies are per host). Run it against the QA copy:
 * opening an inquiry marks it "in progress" by design.
 */
'use strict';
const fs = require('fs');
const path = require('path');
const { chromium } = require(process.env.PW_MODULE || 'playwright');

const BASE = (process.env.BASE || 'http://localhost:8096').replace(/\/$/, '');
const HOST = new URL(BASE).host;
const OUT = path.resolve(process.argv[2] || 'admin-audit');
const KEYS = JSON.parse(fs.readFileSync(process.env.KEYS, 'utf8'));
fs.mkdirSync(OUT, { recursive: true });

const pages = [
  ['/admin', []], ['/admin/products', []], ['/admin/products/create', []],
  ...KEYS.products.map((k) => [`/admin/products/${k}/edit`, ['tabs']]),
  ['/admin/categories', []], ['/admin/categories/create', []], ...KEYS.categories.map((k) => [`/admin/categories/${k}/edit`, []]),
  ['/admin/services', []], ['/admin/services/create', []], ...KEYS.services.map((k) => [`/admin/services/${k}/edit`, []]),
  ['/admin/inquiries', []], ...KEYS.inquiries.map((k) => [`/admin/inquiries/${k}`, []]),
  ['/admin/settings', []], ['/admin/profile', []],
];

async function check(page, url, opts) {
  const issues = [];
  const add = (type, detail) => issues.push({ type, detail });
  const onConsole = (m) => { if (m.type() === 'error') add('console-error', m.text().slice(0, 300)); };
  const onPageError = (e) => add('js-exception', String(e).slice(0, 300));
  const onResponse = (r) => { if (r.status() >= 500 || (r.status() >= 400 && r.request().resourceType() === 'image')) add('http-error', `${r.status()} ${r.request().method()} ${r.url()}`); };
  page.on('console', onConsole); page.on('pageerror', onPageError); page.on('response', onResponse);
  const res = await page.goto(BASE + url, { waitUntil: 'networkidle', timeout: 45000 });
  if (!res || res.status() !== 200) add('http-status', res ? res.status() : 0);
  if (page.url().includes('/login')) add('not-logged-in', page.url());

  const tabs = opts.includes('tabs') ? await page.getByRole('tab').all() : [];
  for (const tab of tabs) {
    await tab.click();
    await page.waitForTimeout(350);
    // Photo upload previews load asynchronously; wait until every item has finished.
    // Wait until no item is still loading; an item that failed ends in an error state instead of idle.
    await page.waitForFunction(() => [...document.querySelectorAll('.filepond--item')].every((e) => !/busy|load$|loading|processing/.test(e.dataset.filepondItemState || '')), null, { timeout: 10000 }).catch(() => add('upload-preview-stuck', `tab ${tabs.indexOf(tab) + 1}`));
    const failedPreviews = await page.$$eval('.filepond--item', (els) => els.filter((e) => /error/.test(e.dataset.filepondItemState || '')).map((e) => e.dataset.filepondItemState));
    failedPreviews.forEach((st) => add('upload-preview-error', `tab ${tabs.indexOf(tab) + 1}: ${st}`));
    await page.waitForTimeout(250);
    await scan(page, add, `tab ${tabs.indexOf(tab) + 1}`);
  }
  if (!tabs.length) await scan(page, add, 'page');
  const livewireError = await page.locator('#livewire-error, dialog#livewire-error').count();
  if (livewireError) add('livewire-error-dialog', 'shown');
  page.off('console', onConsole); page.off('pageerror', onPageError); page.off('response', onResponse);
  return issues;
}

async function scan(page, add, where) {
  const r = await page.evaluate((host) => {
    const out = { broken: [], foreign: [], overflow: null };
    for (const img of document.images) {
      const visible = !!(img.offsetWidth || img.offsetHeight || img.getClientRects().length);
      if ((img.complete && img.naturalWidth === 0) || (!img.complete && visible)) out.broken.push(img.currentSrc || img.src);
      const src = img.currentSrc || img.src;
      if (/^https?:/.test(src) && new URL(src).host !== host) out.foreign.push(src);
    }
    const vw = document.documentElement.clientWidth;
    if (document.documentElement.scrollWidth > vw + 1) out.overflow = `${document.documentElement.scrollWidth} > ${vw}`;
    return out;
  }, HOST);
  r.broken.forEach((s) => add('broken-image', `${where}: ${s}`));
  r.foreign.forEach((s) => add('foreign-host-image', `${where}: ${s}`));
  if (r.overflow) add('horizontal-overflow', `${where}: ${r.overflow}`);
}

(async () => {
  const browser = await chromium.launch({ executablePath: process.env.CHROME || '/usr/bin/google-chrome' });
  const report = { base: BASE, results: [] };
  for (const [vp, opts] of [['desktop', { viewport: { width: 1440, height: 900 } }], ['phone', { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true }]]) {
    const ctx = await browser.newContext({ ...opts, storageState: process.env.AUTH_STATE });
    const page = await ctx.newPage();
    const list = vp === 'desktop' ? pages : pages.filter(([u]) => /^\/admin(\/products|\/inquiries|\/settings|\/categories)?$/.test(u) || u.endsWith('hap-ax2/edit'));
    for (const [url, o] of list) {
      const issues = await check(page, url, vp === 'desktop' ? o : []).catch((e) => [{ type: 'audit-crash', detail: String(e).slice(0, 300) }]);
      report.results.push({ viewport: vp, url, issues });
      process.stdout.write(issues.length ? '!' : '.');
    }
    await ctx.close();
  }
  await browser.close();
  process.stdout.write('\n');
  const byType = {};
  for (const r of report.results) for (const i of r.issues) byType[i.type] = (byType[i.type] || 0) + 1;
  report.summary = { pagesChecked: report.results.length, issuesByType: byType };
  fs.writeFileSync(path.join(OUT, 'admin-audit.json'), JSON.stringify(report, null, 1));
  console.log(JSON.stringify(report.summary, null, 1));
})().catch((e) => { console.error(e); process.exit(1); });
