#!/usr/bin/env node
/*
 * Browser-driven admin workflow test against an ISOLATED QA copy of the database.
 * Refuses to run unless the QA server answers on QA_BASE and the PHP checks connect to `mikrotik_qa`.
 *
 * Usage: PW_MODULE=<playwright> AUTH_STATE=<storageState.json> FIXTURES=<dir> node tools/qa/admin-e2e.cjs <out-dir>
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const { chromium } = require(process.env.PW_MODULE || 'playwright');

const BASE = process.env.QA_BASE || 'http://127.0.0.1:8096';
const QA_DB = 'mikrotik_qa';
const OUT = path.resolve(process.argv[2] || 'admin-qa');
const FIX = process.env.FIXTURES;
const PROJECT = path.resolve(__dirname, '..', '..');
fs.mkdirSync(OUT, { recursive: true });

const P = { name: 'QA Тест-роутер X1', name2: 'QA Тест-роутер X1 Pro', slug: 'qa-test-router-x1', code: 'QA-X1-TEST' };
const C = { slug: 'qa-test-category', ru: 'QA Тест-категория', ru2: 'QA Тест-категория (изм.)', tk: 'QA Synag kategoriýasy', en: 'QA Test category' };
const results = [];
let shot = 0;

/* ---------- helpers ---------- */
function db(php) {
  const guard = `if (DB::connection()->getDatabaseName() !== '${QA_DB}') { echo json_encode(['error' => 'WRONG DATABASE']); return; } `;
  const out = execFileSync('php', ['artisan', 'tinker', '--execute', guard + php], { cwd: PROJECT, env: { ...process.env, DB_DATABASE: QA_DB } }).toString().trim();
  const json = JSON.parse(out.split('\n').pop());
  if (json && json.error) throw new Error(json.error);
  return json;
}
async function pub(ctx, url) {
  const r = await ctx.request.get(BASE + url, { failOnStatusCode: false, maxRedirects: 0 });
  return { status: r.status(), text: await r.text() };
}
async function snap(page, name, full = false) {
  const file = path.join(OUT, `${String(++shot).padStart(2, '0')}-${name}.png`);
  await page.screenshot({ path: file, fullPage: full });
  return path.basename(file);
}
const f = (page, id) => page.locator(`[id="${id}"]`);
async function settle(page, ms = 700) { await page.waitForLoadState('networkidle'); await page.waitForTimeout(ms); }
async function tab(page, name) { await page.locator('.fi-tabs-item', { hasText: name }).first().click(); await page.waitForTimeout(400); }
async function langTab(page, name) { await page.locator('.fi-tabs-item:visible', { hasText: name }).first().click(); await page.waitForTimeout(250); }
async function formErrors(page) { return (await page.locator('.fi-fo-field-wrp-error-message, [data-validation-error]').allInnerTexts()).filter(Boolean); }
async function clickSave(page, label) {
  await page.locator('.fi-form-actions button, .fi-ac button, form button', { hasText: label }).first().click();
  await settle(page, 1200);
  const errs = await formErrors(page);
  if (errs.length) throw new Error('validation: ' + errs.join(' | '));
}
async function chooseSelect(page, label, option) {
  const wrap = page.locator('.fi-fo-field').filter({ has: page.locator('label', { hasText: label }) }).first();
  await wrap.locator('button, [role="combobox"]').first().click();
  const pattern = option instanceof RegExp ? option : new RegExp(option.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/-/g, '[-\u2011]'));
  await page.locator('[role="option"]:visible', { hasText: pattern }).first().click();
  await page.waitForTimeout(400);
}
async function rowOf(page, text) { return page.locator('.fi-ta-row', { hasText: text }).first(); }
let productReady = false;
const serverErrors = [];
async function step(n, title, fn, { needsProduct = false } = {}) {
  const rec = { n, title, status: 'PASS', checks: [], screenshots: [] };
  const before = serverErrors.length;
  if (needsProduct && !productReady) {
    rec.status = 'SKIP'; rec.error = 'skipped: depends on step 1 (test product not created)';
  } else {
    try { await fn(rec); } catch (e) { rec.status = 'FAIL'; rec.error = String(e.message || e).slice(0, 400); }
  }
  if (serverErrors.length > before) {
    rec.status = 'FAIL';
    rec.error = `HTTP 5xx during step: ${serverErrors.slice(before).join('; ')}` + (rec.error ? ` | ${rec.error}` : '');
  }
  results.push(rec);
  console.log(`${rec.status}  ${n}. ${title}${rec.error ? ' — ' + rec.error : ''}`);
}
function check(rec, label, ok, detail = '') {
  rec.checks.push({ label, ok: !!ok, detail: String(detail).slice(0, 200) });
  if (!ok) throw new Error(`check failed: ${label} ${detail}`);
}

/* ---------- run ---------- */
(async () => {
  const dbName = db("echo json_encode(['db' => DB::connection()->getDatabaseName()]);").db;
  if (dbName !== QA_DB) throw new Error('not the QA database');
  const browser = await chromium.launch({ executablePath: process.env.CHROME || '/usr/bin/google-chrome' });
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 1000 }, storageState: process.env.AUTH_STATE });
  const page = await ctx.newPage();
  page.setDefaultTimeout(10000);
  const consoleErrors = [];
  page.on('console', (m) => { if (m.type() === 'error') consoleErrors.push(m.text().slice(0, 200)); });
  page.on('response', (r) => { if (r.status() >= 500) serverErrors.push(`${r.status()} ${r.request().method()} ${r.url().replace(BASE, '')}`); });
  const anon = await browser.newContext();

  await step(1, 'Create a product (unpublished) with name, code, category, price, texts in three languages, one photo and one specification', async (rec) => {
    await page.goto(BASE + '/admin/products/create'); await settle(page);
    await f(page, 'form.name').fill(P.name); await f(page, 'form.name').blur(); await page.waitForTimeout(500);
    await f(page, 'form.slug').fill(P.slug);
    await f(page, 'form.product_code').fill(P.code);
    await f(page, 'form.brand').fill('QA Brand');
    await chooseSelect(page, 'Категория', 'Wi-Fi роутеры');
    const pubToggle = page.locator('.fi-fo-field').filter({ has: page.locator('label', { hasText: 'Опубликован на сайте' }) }).locator('[role="switch"]').first();
    if ((await pubToggle.getAttribute('aria-checked')) === 'true') await pubToggle.click();
    await tab(page, 'Цена и наличие');
    await f(page, 'form.price').fill('1234');
    await tab(page, 'Описание');
    await f(page, 'form.summary.ru').fill('Тестовый товар для проверки админ-панели.');
    await f(page, 'form.description.ru').fill('Первое описание тестового товара.');
    await langTab(page, 'Туркменский'); await f(page, 'form.summary.tk').fill('Synag harydy (QA).');
    await langTab(page, 'Английский'); await f(page, 'form.summary.en').fill('Test product for admin QA.');
    await tab(page, 'Фото');
    await page.locator('input[type="file"]').first().setInputFiles(path.join(FIX, 'qa-test-image-1.jpg'));
    await page.waitForTimeout(3500); await settle(page);
    const alt = page.locator('input[id^="form.images."][id$=".alt.ru"]').first();
    if (await alt.count()) await alt.fill('QA тестовое фото 1');
    await tab(page, 'Характеристики');
    const label = page.locator('input[id^="form.specifications."][id$=".label.ru"]').first();
    if (!(await label.count())) await page.locator('button', { hasText: 'Добавить характеристику' }).first().click();
    await page.locator('input[id^="form.specifications."][id$=".group.ru"]').first().fill('QA группа');
    await page.locator('input[id^="form.specifications."][id$=".label.ru"]').first().fill('Порты');
    await page.locator('textarea[id^="form.specifications."][id$=".value.ru"]').first().fill('2 × Ethernet');
    await clickSave(page, 'Создать');
    check(rec, 'redirected to the edit page', /\/admin\/products\/[^/]+\/edit/.test(page.url()), page.url());
    rec.screenshots.push(await snap(page, 'product-created'));
    const row = db(`$p = App\\Models\\Product::where('slug', '${P.slug}')->first(); echo json_encode($p ? ['published' => $p->is_published, 'price' => (float) $p->price, 'category' => $p->category?->slug, 'images' => $p->images()->count(), 'specs' => $p->specifications()->count(), 'summary_tk' => $p->translate('summary', 'tk'), 'summary_en' => $p->translate('summary', 'en'), 'paths' => $p->images()->pluck('path')] : null);`);
    check(rec, 'DB: product stored', row, JSON.stringify(row));
    check(rec, 'DB: unpublished, price 1234, category wifi-routers', !row.published && row.price === 1234 && row.category === 'wifi-routers', JSON.stringify(row));
    check(rec, 'DB: 1 image, 1 specification, TK/EN summaries', row.images === 1 && row.specs === 1 && row.summary_tk && row.summary_en, JSON.stringify(row));
    const img = row.paths[0];
    const variants = fs.readdirSync(path.join(PROJECT, 'public/uploads', path.dirname(img))).filter((x) => x.startsWith(path.basename(img, path.extname(img))));
    check(rec, 'disk: original + WebP variants written', variants.some((v) => v.endsWith('.webp')), variants.join(', '));
    productReady = true;
    const pg = await pub(anon, `/product/${P.slug}`);
    check(rec, 'public: unpublished product returns 404', pg.status === 404, pg.status);
  });

  await step(6, 'Publish the product from the products table toggle', async (rec) => {
    await page.goto(BASE + '/admin/products?tableSearch=' + encodeURIComponent('QA')); await settle(page);
    const row = await rowOf(page, P.code);
    await row.locator('[role="switch"]').nth(0).click(); await settle(page, 1200);
    rec.screenshots.push(await snap(page, 'product-published-toggle'));
    check(rec, 'DB: published', db(`echo json_encode(App\\Models\\Product::where('slug', '${P.slug}')->value('is_published'));`) === true);
    const pg = await pub(anon, `/product/${P.slug}`);
    check(rec, 'public: product page 200', pg.status === 200, pg.status);
    check(rec, 'public: product listed in its catalog category', (await pub(anon, '/catalog/wifi-routers')).text.includes(P.name));
  }, { needsProduct: true });

  await step(2, 'Edit the product name and description', async (rec) => {
    await page.goto(BASE + `/admin/products/${P.slug}/edit`); await settle(page);
    await f(page, 'form.name').fill(P.name2);
    await tab(page, 'Описание');
    await f(page, 'form.description.ru').fill('Изменённое описание тестового товара (QA).');
    await clickSave(page, 'Сохранить');
    rec.screenshots.push(await snap(page, 'product-edited'));
    const pg = await pub(anon, `/product/${P.slug}`);
    check(rec, 'public: new name shown', pg.text.includes(P.name2));
    check(rec, 'public: new description shown', pg.text.includes('Изменённое описание тестового товара (QA).'));
    const pe = await pub(anon, `/en/product/${P.slug}`);
    check(rec, 'public EN: same model name (names are not translated)', pe.text.includes(P.name2));
  }, { needsProduct: true });

  await step(3, 'Change the price inline in the products table', async (rec) => {
    await page.goto(BASE + '/admin/products?tableSearch=' + encodeURIComponent('QA')); await settle(page);
    const row = await rowOf(page, P.code);
    const input = row.locator('input[type="number"]').first();
    await input.fill('4321'); await input.press('Tab'); await settle(page, 1500);
    rec.screenshots.push(await snap(page, 'price-inline'));
    check(rec, 'DB: price 4321', db(`echo json_encode((float) App\\Models\\Product::where('slug', '${P.slug}')->value('price'));`) === 4321);
    const ru = (await pub(anon, `/product/${P.slug}`)).text;
    const en = (await pub(anon, `/en/product/${P.slug}`)).text;
    check(rec, 'public RU: "4 321" (narrow space)', /4 321/.test(ru));
    check(rec, 'public EN: "4,321"', en.includes('4,321'));
  }, { needsProduct: true });

  await step(4, 'Upload a second product image', async (rec) => {
    await page.goto(BASE + `/admin/products/${P.slug}/edit`); await settle(page);
    await tab(page, 'Фото');
    await page.locator('button', { hasText: 'Добавить фото' }).first().click(); await page.waitForTimeout(800);
    const inputs = page.locator('input[type="file"]');
    await inputs.nth((await inputs.count()) - 1).setInputFiles(path.join(FIX, 'qa-test-image-2.jpg'));
    await page.waitForTimeout(3500); await settle(page);
    await clickSave(page, 'Сохранить');
    rec.screenshots.push(await snap(page, 'second-image', true));
    check(rec, 'DB: 2 images', db(`echo json_encode(App\\Models\\Product::where('slug', '${P.slug}')->first()->images()->count());`) === 2);
    const pg = (await pub(anon, `/product/${P.slug}`)).text;
    check(rec, 'public: gallery has 2 thumbnails', (pg.match(/data-gallery-thumb/g) || []).length === 2, (pg.match(/data-gallery-thumb/g) || []).length);
    const img = await pub(anon, (pg.match(/\/uploads\/products\/[^"' ]+\.webp/) || [''])[0].replace(BASE, ''));
    check(rec, 'public: WebP variant served', img.status === 200, img.status);
  }, { needsProduct: true });

  await step(5, 'Change specifications (edit one value, add one row)', async (rec) => {
    await page.goto(BASE + `/admin/products/${P.slug}/edit`); await settle(page);
    await tab(page, 'Характеристики');
    await page.waitForTimeout(600);
    for (let guard = 0; guard < 20 && await page.locator('.fi-fo-repeater-item.fi-collapsed:visible').count(); guard++) {
      await page.locator('.fi-fo-repeater-item.fi-collapsed:visible .fi-fo-repeater-item-header').first().click({ position: { x: 300, y: 12 } });
      await page.waitForTimeout(300);
    }
    await page.locator('textarea[id^="form.specifications."][id$=".value.ru"]').first().fill('4 × Gigabit Ethernet');
    await page.locator('button', { hasText: 'Добавить характеристику' }).first().click(); await page.waitForTimeout(800);
    await page.locator('input[id^="form.specifications."][id$=".group.ru"]').last().fill('QA группа');
    await page.locator('input[id^="form.specifications."][id$=".label.ru"]').last().fill('Питание');
    await page.locator('textarea[id^="form.specifications."][id$=".value.ru"]').last().fill('PoE-in 24 В');
    await clickSave(page, 'Сохранить');
    rec.screenshots.push(await snap(page, 'specs-edited'));
    check(rec, 'DB: 2 specifications', db(`echo json_encode(App\\Models\\Product::where('slug', '${P.slug}')->first()->specifications()->count());`) === 2);
    const pg = (await pub(anon, `/product/${P.slug}`)).text;
    check(rec, 'public: edited value shown', pg.includes('4 × Gigabit Ethernet'));
    check(rec, 'public: new row shown', pg.includes('PoE-in 24 В'));
  }, { needsProduct: true });

  await step(7, 'Mark the product as featured (homepage)', async (rec) => {
    check(rec, 'public: not on homepage before', !(await pub(anon, '/')).text.includes(P.name2));
    await page.goto(BASE + '/admin/products?tableSearch=' + encodeURIComponent('QA')); await settle(page);
    const row = await rowOf(page, P.code);
    await row.locator('[role="switch"]').nth(1).click(); await settle(page, 1200);
    rec.screenshots.push(await snap(page, 'featured-toggle'));
    check(rec, 'DB: featured', db(`echo json_encode(App\\Models\\Product::where('slug', '${P.slug}')->value('is_featured'));`) === true);
    check(rec, 'public: shown on homepage', (await pub(anon, '/')).text.includes(P.name2));
  }, { needsProduct: true });

  await step(8, 'Create a category in three languages, rename it, move the product into it', async (rec) => {
    await page.goto(BASE + '/admin/categories/create'); await settle(page);
    await f(page, 'form.name.ru').fill(C.ru); await f(page, 'form.name.tk').fill(C.tk); await f(page, 'form.name.en').fill(C.en);
    await f(page, 'form.slug').fill(C.slug);
    await clickSave(page, 'Создать');
    await page.goto(BASE + `/admin/categories/${C.slug}/edit`); await settle(page);
    await f(page, 'form.name.ru').fill(C.ru2);
    await clickSave(page, 'Сохранить');
    await page.goto(BASE + `/admin/products/${P.slug}/edit`); await settle(page);
    await chooseSelect(page, 'Категория', C.ru2);
    await clickSave(page, 'Сохранить');
    await page.goto(BASE + '/admin/categories'); await settle(page);
    rec.screenshots.push(await snap(page, 'categories-table'));
    const cell = (await (await rowOf(page, C.slug)).innerText());
    check(rec, 'admin table: renamed category shown once (no "A, A, A")', cell.includes(C.ru2) && !cell.includes(C.ru2 + ', '), cell.split('\n')[0]);
    const row = db(`$c = App\\Models\\Category::where('slug', '${C.slug}')->first(); echo json_encode(['name' => $c->name, 'products' => $c->products()->count()]);`);
    check(rec, 'DB: names stored per language, product moved', row.name.ru === C.ru2 && row.name.tk === C.tk && row.name.en === C.en && row.products === 1, JSON.stringify(row));
    for (const [loc, pre, name] of [['ru', '', C.ru2], ['tk', '/tk', C.tk], ['en', '/en', C.en]]) {
      const pg = await pub(anon, `${pre}/catalog/${C.slug}`);
      check(rec, `public ${loc}: category page shows its ${loc} name and the product`, pg.status === 200 && pg.text.includes(name) && pg.text.includes(P.name2), pg.status);
    }
  }, { needsProduct: true });

  await step(9, 'Edit a service', async (rec) => {
    const before = db("echo json_encode(App\\Models\\Service::find(1)->translate('title', 'ru'));");
    await page.goto(BASE + '/admin/services/1/edit'); await settle(page);
    await f(page, 'form.title.ru').fill(before + ' (QA)');
    await clickSave(page, 'Сохранить');
    rec.screenshots.push(await snap(page, 'service-edited'));
    check(rec, 'DB: title updated', db("echo json_encode(App\\Models\\Service::find(1)->translate('title', 'ru'));") === before + ' (QA)');
    check(rec, 'public: /services shows the new title', (await pub(anon, '/services')).text.includes(before + ' (QA)'));
    await page.goto(BASE + '/admin/services'); await settle(page);
    const cell = await page.locator('.fi-ta-row').first().innerText();
    check(rec, 'admin table: title rendered once', !cell.includes(before + ', '), cell.split('\n')[0]);
  });

  await step(10, 'Change inquiry status (fictional inquiry submitted through the QA site)', async (rec) => {
    const pp = await anon.newPage();
    await pp.goto(BASE + '/contact'); await settle(pp);
    const form = pp.locator('form[data-inquiry-form]').first();
    await form.locator('input[name="name"]').fill('QA Демо-клиент');
    await form.locator('input[name="phone"]').fill('+993 60 000000');
    await form.locator('textarea[name="message"]').fill('[QA] Тестовая заявка в копии базы.');
    await pp.waitForTimeout(3500);
    await Promise.all([pp.waitForURL(/contact/), form.locator('button[type="submit"]').click()]);
    await settle(pp);
    check(rec, 'public: success message', await pp.locator('#inquiry [role="status"]').count() > 0);
    await pp.close();
    const id = db("echo json_encode(App\\Models\\Inquiry::where('name', 'QA Демо-клиент')->value('id'));");
    check(rec, 'DB: inquiry stored as "new"', id && db(`echo json_encode(App\\Models\\Inquiry::find(${id})->status->value);`) === 'new', id);
    await page.goto(BASE + `/admin/inquiries/${id}`); await settle(page);
    check(rec, 'DB: opening it marks it "in_progress"', db(`echo json_encode(App\\Models\\Inquiry::find(${id})->status->value);`) === 'in_progress');
    await page.locator('button', { hasText: 'Изменить статус' }).first().click(); await page.waitForTimeout(800);
    const modal = page.locator('.fi-modal-window:visible').last();
    await modal.locator('label', { hasText: 'Отвечено' }).first().click();
    await modal.locator('.fi-modal-footer button, button[type="submit"]', { hasText: /Отправить|Сохранить|Подтвердить/ }).first().click(); await settle(page, 1200);
    rec.screenshots.push(await snap(page, 'inquiry-status'));
    check(rec, 'DB: status "answered"', db(`echo json_encode(App\\Models\\Inquiry::find(${id})->status->value);`) === 'answered');
    rec.inquiryId = id;
  });

  await step(11, 'Add an internal note to the inquiry', async (rec) => {
    const id = db("echo json_encode(App\\Models\\Inquiry::where('name', 'QA Демо-клиент')->value('id'));");
    await page.goto(BASE + `/admin/inquiries/${id}`); await settle(page);
    await page.locator('button', { hasText: 'Добавить заметку' }).first().click(); await page.waitForTimeout(800);
    const modal = page.locator('.fi-modal-window:visible').last();
    await modal.locator('textarea').first().fill('[QA] Перезвонить завтра.');
    await modal.locator('button', { hasText: /^Создать$|Создать/ }).first().click(); await settle(page, 1200);
    rec.screenshots.push(await snap(page, 'inquiry-note', true));
    const note = db(`$n = App\\Models\\Inquiry::find(${id})->notes()->latest('id')->first(); echo json_encode($n ? ['body' => $n->body, 'author' => $n->user?->name] : null);`);
    check(rec, 'DB: note stored with author', note && note.body === '[QA] Перезвонить завтра.' && note.author, JSON.stringify(note));
    check(rec, 'admin: note listed on the inquiry', (await page.content()).includes('[QA] Перезвонить завтра.'));
  });

  await step(12, 'Update website contact settings (phone, WhatsApp, e-mail, address)', async (rec) => {
    check(rec, 'public before: no e-mail reply option', !(await pub(anon, '/contact')).text.includes('value="email"'));
    const previousWhatsapp = ((await pub(anon, '/')).text.match(/wa\.me\/(\d+)/) || [])[1];
    await page.goto(BASE + '/admin/settings'); await settle(page);
    await f(page, 'form.phone').fill('+99360000001');
    await f(page, 'form.whatsapp').fill('99360000001');
    await f(page, 'form.email').fill('qa-shop@example.test');
    await f(page, 'form.address.ru').fill('QA: ул. Тестовая, 1');
    await clickSave(page, 'Сохранить');
    rec.screenshots.push(await snap(page, 'settings-saved'));
    const home = (await pub(anon, '/')).text;
    check(rec, 'public: WhatsApp links use the new number', home.includes('wa.me/99360000001') && !(previousWhatsapp && previousWhatsapp !== '99360000001' && home.includes('wa.me/' + previousWhatsapp)));
    check(rec, 'public: tel: link uses the new phone', home.includes('tel:+99360000001'));
    check(rec, 'public: e-mail and address shown', home.includes('qa-shop@example.test') && home.includes('QA: ул. Тестовая, 1'));
    const contact = (await pub(anon, '/contact')).text;
    check(rec, 'public: e-mail reply option now offered (P7 behaviour)', contact.includes('value="email"'));
  });

  await step(13, 'Unpublish the product again (edit form toggle)', async (rec) => {
    await page.goto(BASE + `/admin/products/${P.slug}/edit`); await settle(page);
    const sw = page.locator('.fi-fo-field').filter({ has: page.locator('label', { hasText: 'Опубликован на сайте' }) }).locator('[role="switch"]').first();
    if ((await sw.getAttribute('aria-checked')) === 'true') await sw.click();
    await clickSave(page, 'Сохранить');
    check(rec, 'DB: unpublished', db(`echo json_encode(App\\Models\\Product::where('slug', '${P.slug}')->value('is_published'));`) === false);
    check(rec, 'public: product page 404', (await pub(anon, `/product/${P.slug}`)).status === 404);
    check(rec, 'public: gone from homepage and catalog', !(await pub(anon, '/')).text.includes(P.name2) && !(await pub(anon, '/catalog')).text.includes(P.name2));
  }, { needsProduct: true });

  const pp = await ctx.newPage();
  await pp.goto(BASE + `/product/${P.slug}`).catch(() => {});
  await browser.close();
  fs.writeFileSync(path.join(OUT, 'admin-e2e-results.json'), JSON.stringify({ base: BASE, database: QA_DB, ran_at: new Date().toISOString(), results, consoleErrors, serverErrors }, null, 2));
  const count = (st) => results.filter((r) => r.status === st).length;
  console.log(`\n${count('PASS')} passed, ${count('FAIL')} failed, ${count('SKIP')} skipped of ${results.length}; console errors: ${consoleErrors.length}`);
})().catch((e) => { console.error(e); process.exit(1); });
