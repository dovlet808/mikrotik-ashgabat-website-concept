# Technical architecture

## Stack

| Layer | Version | Notes |
|---|---|---|
| PHP | 8.3.6 (local) | Laravel 13 needs 8.3–8.5 |
| Laravel | 13.39.0 | |
| Filament | 5.11.0 | admin panel at `/admin` |
| Livewire | 4.4.8 | used by Filament only; the public site ships no Livewire JS |
| MariaDB | 11.4 (Docker `mikrotik-db`, 127.0.0.1:3310) | MySQL 8 also works |
| Tailwind CSS | 4.3.3 | via `@tailwindcss/vite` |
| Vite | 8.3.5, laravel-vite-plugin 3 | fonts downloaded at build time and self-hosted |
| Node | 20.20.2 | build-time only, no Node server in production |
| Composer | 2.11.1 | |

Production needs **PHP + MySQL/MariaDB + Apache/LiteSpeed (or nginx)** only.

## Directory layout

```
app/
  Console/Commands/CreateAdminUser.php   php artisan app:create-admin
  Enums/                                 Availability, InquiryStatus, InquiryType, PreferredContact, Segment
  Filament/
    Pages/ManageSettings.php             website settings page
    Pages/EditProfile.php                profile + admin language
    Resources/{Products,Categories,Services,Inquiries}/  (Resource, Schemas, Tables, Pages, RelationManagers)
    Support/Translatable.php             RU/TM/EN field builder
    Widgets/                             dashboard stats + latest inquiries
  Http/
    Controllers/                         Home, Catalog, Product, Service, Solution, Contact, Inquiry, Sitemap, Robots
    Middleware/                          SetLocale, SetAdminLocale, SecurityHeaders
    Requests/StoreInquiryRequest.php     validation + spam checks
  Mail/NewInquiryMail.php
  Models/                                Category, Product, ProductImage, ProductSpecification, Service, Inquiry, InquiryNote, Setting, User
  Models/Concerns/HasTranslations.php
  Support/                               Settings, CatalogFilters, Money, Media/ImageVariants
  helpers.php                            localized_route(), locale_switch_url(), setting(), setting_text()
config/site.php                          locales, currency, contact defaults, indexing switch, rate limits, image widths
database/data/catalog.php                demo catalogue compiled from the business's public listings
database/seeders/                        CatalogSeeder, DemoInquirySeeder (optional)
lang/{ru,tk,en}/site.php, lang/{ru,en}/admin.php
resources/views/
  components/                            layouts/app, product-card, price, availability, section-head, inquiry-form, editorial/product images
  partials/                              header, footer, breadcrumbs, page-header, contact tiles, language switcher, mobile action bar
  pages/                                 home, catalog, product, services, solutions, contact
  errors/                                404, 419, 429 (site layout) · 500, 503 (standalone, no DB)
media/                                   local photo masters and editorial originals (not in the repository)
tools/media/                             web-asset build script (editorial WebP sets, social image)
tools/qa/                                browser-driven audits and admin workflow tests (Playwright)
deployment/                              DirectAdmin kit, release builder, VPS config examples
tests/                                   PHPUnit: Unit + Feature + Feature/Admin
```

## Request flow and localisation

- Russian (default) is served **without a prefix**: `/`, `/catalog`, `/product/hap-ax2`.
- Turkmen and English are prefixed: `/tk/...`, `/en/...`. `routes/web.php` registers one closure of routes twice. The prefixed copy is named `localized.*`, and `{locale}` is constrained to `tk|en`, so `/de/...` returns 404.
- `SetLocale` sets the app locale and `URL::defaults`, then removes the `locale` parameter so controllers stay locale-agnostic.
- `localized_route($name, $params, $locale)` builds links. `locale_switch_url($locale)` rebuilds the current URL (route parameters and filter query string) in another language and falls back to home for routes without a localized twin.
- Every page emits `<link rel="alternate" hreflang>` for ru, tk, en and x-default, plus a canonical URL.

## Catalogue

`CatalogFilters` (value object) reads `q`, `segment`, `availability` and `sort` from the query string and validates them against enums or whitelists; invalid values are ignored.

- **Search:** each word must appear in `products.search_text` (`LIKE`, wildcards escaped). That column covers RU/TM/EN text, specs, codes and category names.
- **Sorting:** recommended (featured, then `sort_order`), price ascending/descending (price-on-request last), name, newest.
- **Pagination:** 12 per page.

The filter form is a plain GET form, so it works without JavaScript; JS only auto-submits on change.

## Images

- Disk `uploads` lives at `public/uploads` (or `APP_PUBLIC_PATH/uploads`). No `storage:link` symlink is needed, which matters on shared hosting.
- Upload URLs are host-relative (`/uploads/...`, override with `UPLOADS_URL`). `ImageVariants::publicUrl()` makes them absolute from the current request, so photos load on any address the site is opened on (LAN IP, localhost, www or not) and pass the `img-src 'self'` policy. In production, `trustHosts()` limits requests to `APP_URL`'s host and its subdomains.
- `ImageVariants` (GD only) writes WebP variants at 480, 960 and 1600 px with EXIF orientation applied. `srcset`/`sizes`, `width`/`height`, `loading="lazy"` and `decoding="async"` are set everywhere; the hero image is `fetchpriority="high"`.
- Product cards fill a 4:5 frame when the photo is between 0.7 and 1.2 aspect; otherwise the photo is shown whole ("mounted print"). Hardware is never cropped away.
- Editorial (generated) images are pre-built WebP sets at 640/1280/1920. The 4K originals are never served.

## Inquiries and spam protection

One endpoint, `POST /inquiries` (and `/tk|en/inquiries`), handles every form.

1. `throttle:inquiry-posts`: 20 POSTs per minute per IP (flood guard).
2. Laravel 13 `PreventRequestForgery` (CSRF token plus origin check).
3. `StoreInquiryRequest` cleans the input: trims it, strips HTML from name and message, and validates against enums and published-product/service existence. Messages are localised.
4. Spam traps:
   - **Honeypot field** (`website`): bots that fill it get a fake success.
   - **Signed timestamp** (`form_started`, encrypted): submissions under 3 s, or with a missing or forged token, get a fake success.
5. Rate limit on *stored* inquiries: 5 per 10 minutes per IP (`INQUIRY_RATE_LIMIT`), with a friendly error that points to WhatsApp.
6. Storage records a product snapshot, locale, page URL and an HMAC'd IP.
7. Optional e-mail to `settings.notification_email` or `INQUIRY_NOTIFY_EMAIL`. Mail failures are logged and never block the customer.

## Admin panel (Filament 5)

- Login only; no registration and no password-reset e-mail (reset via `php artisan app:create-admin`).
- Filament's built-in login throttling.
- `User::canAccessPanel()` requires `is_admin`; `is_admin` is not mass-assignable.
- Per-user panel language (RU/EN) via the profile page and the persistent `SetAdminLocale` middleware.
- Resources: products, categories, services, inquiries (read, status, notes). Plus a Settings page and a dashboard.

## Security

| Concern | Measure |
|---|---|
| Headers | `SecurityHeaders` middleware sets `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy` and `COOP`. HSTS is added when the request is HTTPS. |
| Content-Security-Policy | Strict policy on public pages (`script-src 'self'`, no third-party origins). The admin panel and Vite dev server are excluded. |
| Indexing | `SITE_INDEXABLE=false` (default) sends `X-Robots-Tag: noindex, nofollow` and a robots `<meta>`, and `robots.txt` disallows everything. |
| Mass assignment | Explicit `$fillable` on every model; strict mode outside production. |
| Uploads | Images only (JPEG/PNG/WebP, max 8 MB), slugified random filenames. `public/uploads/.htaccess` denies script execution. |
| Secrets | `.env` is git-ignored and never inside `public_html`. No credentials or contact details in source: contact channels come from `SITE_PHONE`, `SITE_WHATSAPP` and `SITE_INSTAGRAM_HANDLE`, and the admin password is generated by command. |
| Errors | `APP_DEBUG=false` in production. 500/503 pages are standalone (no DB, no stack traces). The private `local` disk's file-serving route is disabled. |
| SQL | Eloquent/query builder bindings only; LIKE wildcards escaped. |
| XSS | Blade escaping everywhere. JSON-LD encoded with `JSON_HEX_TAG`. HTML stripped from form input. |

## Performance

| Item | Size / note |
|---|---|
| Public JS | 2.9 KB (1.1 KB gzipped); no framework |
| CSS | 49 KB (9.6 KB gzipped) |
| Fonts | self-hosted subsets: Onest 400/500/600, JetBrains Mono 400/500 (~15–22 KB each woff2); two weights preloaded |
| Home HTML | ~120 KB raw, ~17 KB gzipped (server compression required; see the deployment `.htaccess`) |
| Images | 14–90 KB WebP depending on width; lazy-loaded below the fold |
| Settings | cached forever; Laravel `optimize` (config/route/view cache) supported |

## Testing

- `php artisan test`: PHPUnit unit and feature tests (public pages, catalogue, inquiries, localisation, admin resources) against a dedicated MariaDB database `mikrotik_test` (see `phpunit.xml`). The uploads disk is faked.
- `tools/qa/`: browser-driven checks with Playwright. `site-audit.cjs` opens every public page in three languages at desktop and phone width; `i18n-audit.cjs` checks translations and layout; `engine-check.cjs` is a Chromium/WebKit smoke check; `admin-audit.cjs` and `admin-e2e.cjs` exercise the admin panel on a disposable database copy made by `qa-db.sh`.
