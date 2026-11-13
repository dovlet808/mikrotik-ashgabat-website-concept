#!/usr/bin/env node
/*
 * Multilingual + layout audit of the public site (read-only).
 * Usage: PW_MODULE=<playwright> node tools/qa/i18n-audit.cjs <out.json>
 * Uses the system Chrome. No form is submitted and no data is written.
 */
'use strict';

const fs = require('fs');
const { execFileSync } = require('child_process');
const { chromium } = require(process.env.PW_MODULE || 'playwright');

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8097';
const OUT = process.argv[2] || 'i18n-audit.json';
const LOCALES = { ru: '', tk: '/tk', en: '/en' };
const LAYOUT_VP = [
  { key: '1440x1000', w: 1440, h: 1000, mobile: false },
  { key: '768x1024', w: 768, h: 1024, mobile: true },
  { key: '390x844', w: 390, h: 844, mobile: true },
  { key: '375x812', w: 375, h: 812, mobile: true },
];

function catalogData() {
  const php = `echo json_encode([
    'categories' => App\\Models\\Category::query()->visible()->whereHas('products', fn ($q) => $q->published())->orderBy('sort_order')->pluck('slug'),
    'products' => App\\Models\\Product::query()->published()->orderBy('sort_order')->get()->map(fn ($p) => ['slug' => $p->slug, 'name' => $p->displayName()]),
  ]);`;
  return JSON.parse(execFileSync('php', ['artisan', 'tinker', '--execute', php], { cwd: process.cwd() }).toString());
}

(async () => {
  const data = catalogData();
  const paths = ['/', '/catalog', ...data.categories.map((c) => `/catalog/${c}`), ...data.products.map((p) => `/product/${p.slug}`), '/services', '/solutions', '/contact', '/stranica-ne-sushchestvuet'];
  const browser = await chromium.launch({ executablePath: process.env.CHROME || '/usr/bin/google-chrome' });
  const report = { base: BASE, generated: new Date().toISOString(), browser: browser.version(), pages: [], layout: [], links: {}, images: {} };
  const internalLinks = new Set();
  const imageUrls = new Set();

  /* 1. Text, language and link checks at desktop width. */
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  const page = await ctx.newPage();
  const diag = { console: [], failed: [] };
  page.on('console', (m) => { if (['error', 'warning'].includes(m.type())) diag.console.push(m.text().slice(0, 200)); });
  page.on('pageerror', (e) => diag.console.push('pageerror: ' + String(e).slice(0, 200)));
  page.on('response', (r) => { if (r.status() >= 400) diag.failed.push(`${r.status()} ${r.url().replace(BASE, '')}`); });

  for (const [locale, prefix] of Object.entries(LOCALES)) {
    for (const p of paths) {
      const url = (prefix + (p === '/' ? '' : p)) || '/';
      diag.console.length = 0; diag.failed.length = 0;
      const resp = await page.goto(BASE + url, { waitUntil: 'networkidle' });
      const info = await page.evaluate(({ locale, prefix }) => {
        const text = document.body.innerText;
        const lines = text.split('\n').map((l) => l.trim()).filter(Boolean);
        const cyr = /[А-Яа-яЁё]/;
        const cyrillicLines = locale === 'ru' ? [] : [...new Set(lines.filter((l) => cyr.test(l)))];
        const rawKeys = [...new Set((text.match(/\b(site|admin|validation|filament[\w-]*)\.[a-z_]+(\.[a-z_]+)+\b/g) || []))];
        const links = [...document.querySelectorAll('a[href]')].map((a) => ({ href: a.getAttribute('href'), abs: a.href, text: a.innerText.trim().slice(0, 40), hreflang: a.getAttribute('hreflang') }));
        const wrongLocaleLinks = links.filter((l) => {
          let u; try { u = new URL(l.abs); } catch (e) { return false; }
          if (u.origin !== location.origin || l.hreflang || u.hash && u.pathname === location.pathname) return false;
          if (/\.(xml|txt|svg|png|jpe?g|webp|css|js)$/.test(u.pathname) || u.pathname.startsWith('/admin')) return false;
          const isTk = u.pathname === '/tk' || u.pathname.startsWith('/tk/');
          const isEn = u.pathname === '/en' || u.pathname.startsWith('/en/');
          return locale === 'ru' ? (isTk || isEn) : !(prefix && (u.pathname === prefix || u.pathname.startsWith(prefix + '/')));
        }).map((l) => `${l.text || '(no text)'} → ${l.href}`);
        const prices = [...document.querySelectorAll('main [class*="font-mono"]')].map((e) => e.innerText.trim()).filter((t) => /\d/.test(t) && /(манат|manat|TMT)/i.test(t)).slice(0, 4);
        const h1 = document.querySelector('h1')?.innerText.trim() || '';
        const imgs = [...document.images].flatMap((i) => [i.currentSrc || i.src, ...(i.srcset || '').split(',').map((s) => s.trim().split(' ')[0])]).filter(Boolean);
        const alternates = [...document.querySelectorAll('link[rel="alternate"][hreflang]')].map((l) => l.hreflang);
        return { lang: document.documentElement.lang, title: document.title, description: document.querySelector('meta[name="description"]')?.content || '', h1, cyrillicLines, rawKeys, wrongLocaleLinks, prices, alternates, internal: links.map((l) => l.abs).filter((h) => h.startsWith(location.origin)), imgs };
      }, { locale, prefix });
      info.internal.forEach((h) => internalLinks.add(h.split('#')[0]));
      info.imgs.forEach((u) => imageUrls.add(new URL(u, BASE).href));
      delete info.internal; delete info.imgs;
      report.pages.push({ locale, path: p, url, status: resp.status(), ...info, console: [...diag.console], failed: [...diag.failed] });
      process.stdout.write('.');
    }
  }
  await ctx.close();

  /* 2. Layout at four widths: horizontal overflow and clipped button/chip labels. */
  for (const vp of LAYOUT_VP) {
    const c = await browser.newContext({ viewport: { width: vp.w, height: vp.h }, deviceScaleFactor: 1, isMobile: vp.mobile, hasTouch: vp.mobile });
    const pg = await c.newPage();
    for (const [locale, prefix] of Object.entries(LOCALES)) {
      for (const p of paths) {
        const url = (prefix + (p === '/' ? '' : p)) || '/';
        await pg.goto(BASE + url, { waitUntil: 'load' });
        const l = await pg.evaluate(() => {
          const de = document.documentElement;
          const clipped = [...document.querySelectorAll('.btn, nav a, button, label span, [class*="rounded-full"]')]
            .filter((e) => e.getClientRects().length && e.scrollWidth > e.clientWidth + 1 && getComputedStyle(e).overflow !== 'visible' && !e.closest('[class*="overflow-x-auto"]'))
            .map((e) => e.innerText.trim().slice(0, 40)).filter(Boolean);
          const header = document.querySelector('[data-site-header]');
          return { sw: de.scrollWidth, cw: de.clientWidth, clipped: [...new Set(clipped)].slice(0, 5), headerHeight: header ? Math.round(header.getBoundingClientRect().height) : null };
        });
        report.layout.push({ viewport: vp.key, locale, path: p, overflow: l.sw > l.cw, scrollWidth: l.sw, clientWidth: l.cw, clipped: l.clipped, headerHeight: l.headerHeight });
      }
      process.stdout.write('+');
    }
    await c.close();
  }

  /* 3. Every internal link and image URL that the pages reference. */
  const req = await browser.newContext();
  for (const u of [...internalLinks].sort()) {
    const r = await req.request.get(u, { maxRedirects: 0, failOnStatusCode: false });
    report.links[u.replace(BASE, '')] = r.status();
  }
  for (const u of [...imageUrls].sort()) {
    const r = await req.request.get(u, { failOnStatusCode: false });
    report.images[u.replace(BASE, '')] = `${r.status()} ${r.headers()['content-type'] || ''}`;
  }
  await req.close();
  await browser.close();
  fs.writeFileSync(OUT, JSON.stringify(report, null, 2));
  console.log(`\nwrote ${OUT}: ${report.pages.length} page checks, ${report.layout.length} layout checks, ${Object.keys(report.links).length} links, ${Object.keys(report.images).length} images`);
})().catch((e) => { console.error(e); process.exit(1); });
