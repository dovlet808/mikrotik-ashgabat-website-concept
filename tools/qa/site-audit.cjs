#!/usr/bin/env node
/*
 * Full audit of the public website in a real browser (Google Chrome via Playwright).
 *
 *   PW_MODULE=<playwright> BASE=http://localhost:8096 node tools/qa/site-audit.cjs <out-dir>
 *
 * Open the site on a host name that differs from APP_URL on purpose (here localhost instead of 127.0.0.1):
 * that is exactly how a phone reaches the dev server (LAN IP), and how visitors may reach production (www or not).
 *
 * Every page from sitemap.xml plus filter, pagination and error variants, in all three languages, at desktop and
 * phone width. Read-only, except FORMS=1, which submits fictional inquiries; use that on the QA copy only.
 */
'use strict';
const fs = require('fs');
const path = require('path');
const { chromium } = require(process.env.PW_MODULE || 'playwright');

const BASE = (process.env.BASE || 'http://localhost:8096').replace(/\/$/, '');
const HOST = new URL(BASE).host;
const OUT = path.resolve(process.argv[2] || 'site-audit');
const FORMS = process.env.FORMS === '1';
const ALLOWED_EXTERNAL = /^(https?:\/\/(wa\.me|api\.whatsapp\.com|(www\.)?instagram\.com|schema\.org)\b|tel:|mailto:)/;
fs.mkdirSync(OUT, { recursive: true });

const VIEWPORTS = {
  desktop: { viewport: { width: 1440, height: 900 } },
  phone: { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 },
};

async function urls() {
  const xml = await (await fetch(`${BASE}/sitemap.xml`)).text();
  const list = [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => m[1].replace(/^https?:\/\/[^/]+/, ''));
  const extra = [];
  for (const p of ['', '/tk', '/en']) {
    extra.push(`${p}/catalog?q=hap`, `${p}/catalog?segment=office&sort=price_asc`, `${p}/catalog?availability=in_stock`, `${p}/catalog?page=2`, `${p}/catalog?q=zzzz-no-match`, `${p}/no-such-page`, `${p}/product/hap-be-lite`);
  }
  return { list: [...new Set(list)], extra };
}

async function auditPage(page, url, expect404) {
  const issues = [];
  const add = (type, detail) => issues.push({ type, detail });
  const failed = [];
  const onConsole = (m) => { if (m.type() === 'error') add('console-error', m.text().slice(0, 300)); };
  const onPageError = (e) => add('js-exception', String(e).slice(0, 300));
  const onFailed = (r) => failed.push(`${r.failure()?.errorText} ${r.url()}`);
  const onResponse = (r) => { if (r.status() >= 400 && r.url() !== BASE + url && r.request().resourceType() !== 'document') add('subresource-http-error', `${r.status()} ${r.url()}`); };
  page.on('console', onConsole); page.on('pageerror', onPageError); page.on('requestfailed', onFailed); page.on('response', onResponse);

  const res = await page.goto(BASE + url, { waitUntil: 'load', timeout: 45000 });
  const status = res ? res.status() : 0;
  if (expect404 ? status !== 404 : status !== 200) add('http-status', `${status} (expected ${expect404 ? 404 : 200})`);

  // Scroll through the page so lazy images load, then wait for the network to settle.
  await page.evaluate(async () => {
    const pause = (ms) => new Promise((r) => setTimeout(r, ms));
    for (let y = 0; y < document.documentElement.scrollHeight; y += Math.floor(innerHeight * 0.7)) { scrollTo(0, y); await pause(60); }
    scrollTo(0, 0);
  });
  await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => add('network-not-idle', 'still loading after 15 s'));
  // A fast scroll can pass lazy images before the browser starts them. Bring each unfinished, rendered image into
  // view and give it time, so only images that really fail are reported.
  await page.evaluate(async () => {
    const rendered = (i) => !!(i.offsetWidth || i.offsetHeight) && getComputedStyle(i).visibility !== 'hidden';
    for (const img of [...document.images].filter((i) => !i.complete && rendered(i))) {
      img.scrollIntoView({ block: 'center' });
      await new Promise((r) => { const t = setTimeout(r, 8000); img.addEventListener('load', () => { clearTimeout(t); r(); }, { once: true }); img.addEventListener('error', () => { clearTimeout(t); r(); }, { once: true }); });
    }
    scrollTo(0, 0);
  });

  const dom = await page.evaluate(({ host, allowed }) => {
    const out = { broken: [], foreign: [], noAlt: [], unnamed: [], dupIds: [], h1: 0, lang: document.documentElement.lang, title: document.title, desc: document.querySelector('meta[name=description]')?.content || '', overflow: null, csp: window.__csp || [], links: [] };
    const visible = (el) => !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    for (const img of document.images) {
      if (img.complete && img.naturalWidth === 0) out.broken.push(img.currentSrc || img.src);
      else if (!img.complete && visible(img)) out.broken.push(`(never finished) ${img.currentSrc || img.src}`);
      if (!img.hasAttribute('alt')) out.noAlt.push(img.src);
    }
    const attrs = [];
    document.querySelectorAll('[src],[href],[srcset],meta[content^="http"],[data-src],[data-srcset]').forEach((el) => {
      for (const a of ['src', 'href', 'content', 'data-src']) if (el.getAttribute(a)) attrs.push(el.getAttribute(a));
      for (const a of ['srcset', 'data-srcset']) if (el.getAttribute(a)) el.getAttribute(a).split(',').forEach((p) => attrs.push(p.trim().split(/\s+/)[0]));
    });
    document.querySelectorAll('script[type="application/ld+json"]').forEach((s) => (s.textContent.match(/https?:\/\/[^"\\\s]+/g) || []).forEach((u) => attrs.push(u.replace(/\\\//g, '/'))));
    for (const u of attrs) {
      if (/^https?:\/\//.test(u)) { const h = new URL(u).host; if (h !== host && !new RegExp(allowed).test(u)) out.foreign.push(u); }
    }
    document.querySelectorAll('a[href]').forEach((a) => { const u = new URL(a.href, location.href); if (u.host === host) out.links.push(u.pathname + u.search); });
    document.querySelectorAll('a, button').forEach((el) => {
      const name = [el.getAttribute('aria-label'), el.textContent, el.getAttribute('title'), ...[...el.querySelectorAll('img')].map((i) => i.alt)].map((v) => (v || '').trim()).find(Boolean);
      if (!name && visible(el)) out.unnamed.push(el.outerHTML.slice(0, 120));
    });
    const ids = {}; document.querySelectorAll('[id]').forEach((el) => { ids[el.id] = (ids[el.id] || 0) + 1; });
    out.dupIds = Object.entries(ids).filter(([, n]) => n > 1).map(([id]) => id);
    out.h1 = document.querySelectorAll('h1').length;
    const vw = document.documentElement.clientWidth;
    if (document.documentElement.scrollWidth > vw + 1) {
      const scrollsX = (el) => { for (let p = el.parentElement; p; p = p.parentElement) { const o = getComputedStyle(p).overflowX; if (o === 'auto' || o === 'scroll' || o === 'hidden' || o === 'clip') return true; } return false; };
      const culprits = [...document.body.querySelectorAll('*')].filter((el) => visible(el) && el.getBoundingClientRect().right > vw + 1 && !scrollsX(el)).slice(0, 5).map((el) => `${el.tagName.toLowerCase()}.${[...el.classList].slice(0, 3).join('.')} right=${Math.round(el.getBoundingClientRect().right)}`);
      out.overflow = { scrollWidth: document.documentElement.scrollWidth, viewport: vw, culprits };
    }
    return out;
  }, { host: HOST, allowed: ALLOWED_EXTERNAL.source });

  dom.broken.forEach((s) => add('broken-image', s));
  [...new Set(dom.foreign)].forEach((u) => add('foreign-host-url', u));
  dom.noAlt.forEach((s) => add('img-without-alt', s));
  dom.unnamed.forEach((s) => add('control-without-name', s));
  dom.dupIds.forEach((s) => add('duplicate-id', s));
  dom.csp.forEach((s) => add('csp-violation', s));
  if (dom.overflow) add('horizontal-overflow', dom.overflow);
  if (!expect404 && dom.h1 !== 1) add('h1-count', dom.h1);
  if (!dom.title) add('missing-title', '');
  if (!expect404 && !dom.desc) add('missing-meta-description', '');
  const wantLang = url.startsWith('/tk') ? 'tk' : url.startsWith('/en') ? 'en' : 'ru';
  if (dom.lang !== wantLang) add('html-lang', `${dom.lang} (expected ${wantLang})`);
  failed.forEach((f) => add('request-failed', f));

  page.off('console', onConsole); page.off('pageerror', onPageError); page.off('requestfailed', onFailed); page.off('response', onResponse);
  return { url, status, issues, links: [...new Set(dom.links)] };
}

async function submitForm(page, url, label) {
  const steps = [];
  await page.goto(BASE + url, { waitUntil: 'load' });
  const form = page.locator('form[action*="inquiries"]').first();
  await form.scrollIntoViewIfNeeded();
  await form.locator('button[type=submit]').click();
  await page.waitForLoadState('load');
  // Required fields stop an empty form in the browser before it is sent; otherwise the server marks the errors.
  const blocked = await page.locator('form[action*="inquiries"] :invalid').count();
  const errors = await page.locator('[aria-invalid="true"], [role=alert]').count();
  steps.push({ step: `${label}: empty submit is stopped with field errors`, ok: blocked + errors > 0, detail: `${blocked} fields flagged by the browser, ${errors} server error markers` });
  const f = page.locator('form[action*="inquiries"]').first();
  await f.locator('input[name=name]').fill('QA Аудит (тест)');
  await f.locator('input[name=phone]').fill('+993 61 00-00-01');
  const msg = f.locator('textarea[name=message]');
  if (await msg.count()) await msg.fill('Тестовая заявка аудита. Не обрабатывать.');
  await page.waitForTimeout(3600); // the form rejects submissions faster than 3 s as spam
  await f.locator('button[type=submit]').click();
  await page.waitForLoadState('load');
  const body = await page.locator('main').innerText();
  const ok = /спасибо|заявка отправлена|получили|thank|sag bol/i.test(body);
  steps.push({ step: `${label}: valid submit succeeds`, ok, detail: ok ? 'success message shown' : body.slice(0, 200) });
  return steps;
}

(async () => {
  const { list, extra } = await urls();
  const browser = await chromium.launch({ executablePath: process.env.CHROME || '/usr/bin/google-chrome' });
  const report = { base: BASE, pages: [], links: [], forms: [], summary: {} };
  for (const [vpName, opts] of Object.entries(VIEWPORTS)) {
    const ctx = await browser.newContext(opts);
    await ctx.addInitScript(() => { window.__csp = []; document.addEventListener('securitypolicyviolation', (e) => window.__csp.push(`${e.violatedDirective} blocked ${e.blockedURI}`)); });
    const page = await ctx.newPage();
    for (const url of [...list, ...extra]) {
      const expect404 = /no-such-page|hap-be-lite/.test(url);
      const r = await auditPage(page, url, expect404).catch((e) => ({ url, status: 0, issues: [{ type: 'audit-crash', detail: String(e).slice(0, 300) }], links: [] }));
      report.pages.push({ viewport: vpName, ...r });
      process.stdout.write(r.issues.length ? '!' : '.');
    }
    await ctx.close();
  }
  process.stdout.write('\n');

  // Links found on the deliberate 404 pages (language switcher) point back to those same missing URLs.
  const allLinks = [...new Set(report.pages.filter((p) => p.status !== 404).flatMap((p) => p.links))].filter((l) => !/\/inquiries$/.test(l));
  for (const l of allLinks) {
    const r = await fetch(BASE + l, { redirect: 'manual' }).catch(() => null);
    const s = r ? r.status : 0;
    if (s >= 400 || s === 0) report.links.push({ link: l, status: s });
  }
  if (FORMS) {
    const ctx = await browser.newContext(VIEWPORTS.desktop);
    const page = await ctx.newPage();
    report.forms.push(...await submitForm(page, '/contact', 'contact form'));
    report.forms.push(...await submitForm(page, '/en/product/hap-ax2', 'product form (EN)'));
    await ctx.close();
  }
  await browser.close();

  const byType = {};
  for (const p of report.pages) for (const i of p.issues) byType[i.type] = (byType[i.type] || 0) + 1;
  report.summary = { pagesChecked: report.pages.length, uniqueUrls: list.length + extra.length, internalLinksChecked: allLinks.length, brokenLinks: report.links.length, issuesByType: byType, formsOk: report.forms.every((f) => f.ok) };
  fs.writeFileSync(path.join(OUT, 'site-audit.json'), JSON.stringify(report, null, 1));
  console.log(JSON.stringify(report.summary, null, 1));
})().catch((e) => { console.error(e); process.exit(1); });
