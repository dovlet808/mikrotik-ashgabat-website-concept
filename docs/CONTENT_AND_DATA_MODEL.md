# Content and data model

How the source material (the business's public price list and Instagram posts) maps into the database and onto the website.

## 1. Flow

```
price list (PDF) ─────┐
Instagram captions ───┼─> database/data/catalog.php ──> CatalogSeeder ──> MariaDB
                      └─> media/products/<slug>/NN-view.jpg   (local only, optional)
                                        │
               CatalogSeeder copies ────┘──> public/uploads/products/<slug>/
                                              + -480/-960/-1600.webp variants
After seeding, the database is the source of truth: the owner edits everything in /admin.
```

The source files and the product photographs belong to the business and are **not part of this repository**. Without `media/products/` the seeder skips photos and the site shows a neutral placeholder for each product.

- `database/data/catalog.php` is the single, auditable record of everything taken from the source. Each product has a `source` note pointing to PDF pages and Instagram posts.
- The seeder only **creates missing records** (matched by slug). Re-running it never overwrites prices or texts the owner changed in the admin panel.
- Editorial images are committed as pre-built WebP sets in `public/images/editorial/`. `tools/media/build_web_assets.py` rebuilds them when the 4K originals are present locally, and builds the social image `public/images/og-default.jpg`.

## 2. Entities

```
users ─────────────┐
                   │ (author)
categories 1 ── n products 1 ── n product_images
                      │ 1 ── n product_specifications
                      │ 1 ── n inquiries n ── 1 services
                                  │ 1 ── n inquiry_notes
settings (key → JSON value)
```

### categories
| Column | Type | Notes |
|---|---|---|
| slug | string, unique | URL `/catalog/{slug}` |
| name | text (JSON translations) | `{"ru": "Wi‑Fi роутеры", "tk": "…", "en": "…"}` |
| description | text (JSON translations), nullable | |
| sort_order | int | |
| is_visible | bool | Hidden categories also hide their products |

Deletion is blocked while products (including soft-deleted ones) reference the category. This is enforced three ways: FK `restrictOnDelete`, a model `deleting` guard, and a guarded admin action. The admin offers **"Перенести товары и удалить"** to reassign the products first.

### products
| Column | Type | Notes |
|---|---|---|
| category_id | FK, nullable | |
| slug | string, unique | URL `/product/{slug}` |
| name | string | **Exact model name**, never translated (e.g. `hAP ax²`) |
| product_code | string, nullable | Exact manufacturer code (e.g. `C52iG-5HaxD2HaxD-TC`) |
| brand | string | MikroTik / Fortinet / Cisco |
| summary, purpose | text (JSON translations) | PDF description and «Цель» line |
| description | longText (JSON translations) | Instagram introduction; paragraphs separated by a blank line |
| price | decimal(12,2), nullable | **Numeric**; `NULL` → «Цена по запросу» |
| currency | char(3) | `TMT` (Turkmen manat) |
| availability | string enum | `in_stock`, `on_order`, `out_of_stock`, `confirm` (default, neutral) |
| segments | text (JSON list) | Audiences: `home`, `small_business`, `office`, `commercial`, `enterprise`, `outdoor`; drives the Solutions page and the catalogue filter |
| is_featured, is_published, sort_order | | Publishing and ordering |
| source_reference | text | Internal provenance note, admin only |
| search_text | text | Lower-cased index of name, code, brand, all translations, specs and category name; rebuilt on every save |
| deleted_at | soft delete | Deleted products can be restored |

### product_images
`path` on the `uploads` disk, translatable `alt`, `width` and `height` (used against layout shift and for choosing the card fit), `sort_order`. The first image is the catalogue photo.

Saving an image generates `-480`, `-960` and `-1600` WebP variants with GD. Replacing or deleting an image removes its files. A product force-delete deletes its images through Eloquent, so the files go too.

### product_specifications
Translatable `group`, `label` and `value`, plus `sort_order`. Rendered as a grouped definition list on the product page.

### services
Translatable `title`, `summary` and `body`; `icon` (Heroicons name); optional `image_path`; `is_confirmed` (the service is stated in the company's own material); `source_reference`; `is_published`; `sort_order`.

### inquiries
`type` (product / service / consultation / contact) and `status` (new / in_progress / answered / closed / spam). Contact fields: `name`, `phone`, optional `email`, `preferred_contact` (whatsapp / phone / email), `message`.

Optional `product_id` and `service_id` (null on delete). `product_snapshot` stores the model, code and price as they were when the inquiry was sent, so later price changes don't rewrite history.

`locale` and `page_url` record where it came from. `ip_hash` is an HMAC, not the raw IP. Also stored: `user_agent` and `handled_at` (set automatically when an inquiry leaves "new").

### inquiry_notes
Internal notes with author (`user_id`), body and timestamp.

### settings
Key/value store, JSON values, cached forever and flushed on save:

| Key | Default | Used for |
|---|---|---|
| phone | `SITE_PHONE` (placeholder `+99300000000`) | header, footer, `tel:` links |
| whatsapp | `SITE_WHATSAPP` (placeholder) | every `wa.me` link (with prefilled message) |
| instagram_handle / instagram_url | `SITE_INSTAGRAM_HANDLE` (empty → hidden) | footer, contact page |
| email | empty | shown only if filled |
| city | Ашхабад | |
| address, working_hours | empty (translatable) | shown only if filled |
| delivery_text, delivery_note | empty → default copy | delivery block |
| price_note | empty → default copy | under prices |
| show_prices | true | switches all prices to «Цена по запросу» |
| notification_email | empty | e-mail copy of each new inquiry |

### users
Standard Laravel users, plus:

- `is_admin`: not mass-assignable; only `app:create-admin` sets it.
- `locale`: `ru` or `en`, the admin panel language.

Panel access requires `is_admin`.

## 3. Translations

- Interface strings: `lang/{ru,tk,en}/site.php` (public) and `lang/{ru,en}/admin.php` (panel). Key parity is enforced by `tests/Unit/TranslationParityTest.php`.
- Content: translatable attributes are JSON objects. `HasTranslations::translate()` returns the current locale's text and falls back to Russian if the translation is empty, so a page never shows a blank where a translation is missing.
- In the admin panel, every translatable field has RU / TM / EN inputs (side by side for short fields, tabs for long texts). Only Russian is required.

## 4. Source → website mapping

| Source item | Database | Website |
|---|---|---|
| PDF "Model" | `products.name` | card title, H1, breadcrumbs, WhatsApp message |
| PDF "Product code" | `products.product_code` | card label, product page "Код продукта" (exact case, never upper-cased) |
| PDF price + "manat/манат" | `products.price` + `currency=TMT` | `2 850 манат` |
| PDF description | `products.summary` | card text, product lead, meta description |
| PDF «Цель:» | `products.purpose` | «Для чего» box |
| PDF/IG audience words (дом, офис, склад, гостиницы…) | `products.segments` | Solutions page lists, catalogue filter «Для кого» |
| IG caption intro | `products.description` | «Описание» |
| IG bullet specs | `product_specifications` | «Характеристики» |
| IG «В наличии» | not imported (dated) | neutral «Наличие уточняйте» until the owner confirms |
| PDF p.23 services | `services` | Home "Услуги", Services page, request form |
| Contact number | `SITE_PHONE` / `SITE_WHATSAPP`, then `settings.phone/whatsapp` | header, footer, CTA buttons, mobile bar |
| PDF/IG photos | `product_images` | catalogue, product gallery, Solutions lists |
