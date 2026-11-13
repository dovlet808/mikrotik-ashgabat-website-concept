# Deployment — DirectAdmin shared hosting

Status: **not deployed.** The concept has never been published. Everything below was rehearsed locally in a simulated DirectAdmin layout (`~/mikrotik-app` plus `domains/<domain>/public_html`): the release built, migrated, seeded and served correctly.

> Deploy only to a **demo (sub)domain you control**. Never to the business's real domain, and never as its "official" site, without written permission. Keep `SITE_INDEXABLE=false` and `SITE_CONCEPT_MODE=true` until the business approves.

## 1. Hosting compatibility

### What to expect
- Laravel 13 requires **PHP ≥ 8.3**. PHP 8.2 will not work.
- Most DirectAdmin hosts offer a per-domain PHP selector. Confirm that 8.3 or newer is available on the plan and that the build is currently patched.
- "Setup Node.js App" is **not needed**. Node is used only on the build machine to compile CSS and JS.

### What to check (5 minutes)
1. In DirectAdmin create the demo subdomain (e.g. `mikrotik.example.com`) and enable **Let's Encrypt SSL**.
2. **Select PHP Version** → 8.4 (or 8.3, patched) for that domain. Enable extensions: `bcmath ctype curl dom exif fileinfo gd intl mbstring openssl pdo_mysql tokenizer xml zip`. Set `upload_max_filesize` and `post_max_size` to at least **10M** and `memory_limit` to at least **256M**.
3. Open `deployment/directadmin/hosting-check.php`, replace `CHECK_TOKEN` with a long random string, upload it to the subdomain's `public_html/`, and open `https://SUBDOMAIN/hosting-check.php?token=…`.
4. Every line must say `OK` (except the ones marked optional). **Delete the file afterwards.**
5. Check **SSH access**: DirectAdmin → *SSH Keys* or ask support. SSH makes the deploy far easier (option A below), but FTP/File Manager also works (option B).

| Requirement | Needed for | If missing |
|---|---|---|
| PHP ≥ 8.3 | everything | ask the host; else VPS (see VPS_REQUIREMENTS.md) |
| pdo_mysql, mbstring, intl, gd (+WebP), fileinfo, zip, openssl, exif | Laravel, Filament, image variants | enable in the PHP selector |
| MySQL/MariaDB database | data | DirectAdmin → MySQL Management |
| A folder **outside** `public_html` | keeps `.env` and code private | if impossible, see §6 |
| `proc_open` / `symlink` | not required (no `storage:link` needed) | — |
| SSH | `php artisan` commands | use option B (database import) |
| Cron | not required (no queues or scheduler) | — |
| SSL | secure cookies, admin login | Let's Encrypt in DirectAdmin |

## 2. Build the release (build machine)

```bash
cd mikrotik-ashgabat-website-concept
./deployment/build-release.sh
```

This produces `dist/mikrotik-release-<stamp>/` (and a `.zip`) containing:

- `app/`: the Laravel application with production `vendor/`. It has **no `.env`**, tests or development tooling.
- `public_html/`: the web root (built assets, images, fonts, a DirectAdmin `index.php` and a hardened `.htaccess`).

## 3. Target layout on the server

```
/home/USER/
├── mikrotik-app/                         ← upload dist/…/app/ here  (NOT web-accessible)
│   ├── .env                              ← create from .env.production.example
│   ├── storage/   (writable)
│   └── bootstrap/cache/ (writable)
└── domains/
    └── example.com/
        └── public_html/mikrotik/         ← or the subdomain's own public_html
            ├── index.php                 ← from dist/…/public_html/
            ├── .htaccess
            ├── build/ images/ fonts/ css/ js/ uploads/
```

DirectAdmin puts sub-domain document roots either at `domains/DOMAIN/public_html/SUB/` or at `domains/SUB.DOMAIN/public_html/`. Check under *Subdomain Management*.

**Edit `APP_ROOT` in `public_html/index.php`** so it points to `/home/USER/mikrotik-app`. An absolute path is safest:

```php
const APP_ROOT = '/home/USER/mikrotik-app';
```

## 4. Database

DirectAdmin → **MySQL Management** → *Create new database*. Note the database name, user and password (DirectAdmin prefixes them with `USER_`). Character set: utf8mb4.

## 5. Configure `.env`

```bash
cp /home/USER/mikrotik-app/.env.production.example /home/USER/mikrotik-app/.env
```

Fill in:
- `APP_KEY`: generate locally with `php artisan key:generate --show` and paste the value.
- `APP_URL=https://SUBDOMAIN`
- `APP_PUBLIC_PATH=/home/USER/domains/DOMAIN/public_html[/SUB]`: the real web root, so artisan writes uploads where the web server reads them.
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (`DB_HOST=localhost`).
- `MAIL_*` from DirectAdmin → *E-Mail Accounts* (optional; used for inquiry notifications).
- Keep `APP_DEBUG=false`, `SITE_INDEXABLE=false`, `SITE_CONCEPT_MODE=true`.

Set permissions: `chmod 600 .env`, and `chmod -R 775 storage bootstrap/cache`.

## 6. Install

### Option A: with SSH (recommended)

```bash
cd /home/USER/mikrotik-app
php -v                                   # must be ≥ 8.3 (the CLI may differ from the web PHP; use /usr/local/php84/bin/php if needed)
php artisan migrate --force
php artisan db:seed --force              # imports the catalogue (and product photos, when media/products is present)
php artisan app:create-admin --email=owner@… --generate   # prints a one-time password
php artisan optimize                     # config/route/view cache
```

### Option B: without SSH (File Manager / FTP + phpMyAdmin)

1. **Locally**, prepare the database and photos for the release:
   ```bash
   php artisan migrate:fresh --seed --force
   php artisan app:create-admin --email=owner@… --generate
   docker exec mikrotik-db mariadb-dump -umikrotik -p mikrotik > dist/mikrotik.sql
   ```
2. Import `dist/mikrotik.sql` in DirectAdmin → **phpMyAdmin** → Import.
3. Upload `public/uploads/` (the product photos and their WebP variants) into `public_html/uploads/`.
4. Upload the `app/` and `public_html/` folders, then create `.env` (§5).
5. Without SSH, Laravel's caches cannot be built on the server. The site runs fine without them. If you ever upload cache files, delete `bootstrap/cache/*.php` after each change to `.env`.

> Do not reuse local passwords. Create the production admin with a fresh generated password.

## 7. Verify

- `https://SUBDOMAIN/` → home page, RU/TM/EN switch, catalogue, a product page, contact form.
- `https://SUBDOMAIN/admin` → log in, change one price, check the site.
- Submit one test inquiry; check it appears under *Заявки*; delete it.
- These must return **404**: `/.env`, `/../mikrotik-app/.env`, `/composer.json`, `/storage/logs/laravel.log`.
- Response headers show `X-Robots-Tag: noindex, nofollow`, and `/robots.txt` says `Disallow: /`.
- Enable the HTTPS redirect in `public_html/.htaccess` once SSL is active.

## 8. Updating later

1. Run `./deployment/build-release.sh` locally.
2. Upload `app/` over the old one, **keeping `.env` and `storage/`**.
3. Upload `public_html/`, **keeping `uploads/`**.
4. With SSH: `php artisan migrate --force && php artisan optimize`.

The seeder never overwrites products the owner has edited, so it is safe to re-run.

**Backups:** DirectAdmin → *Create/Restore Backups* (database plus `public_html/uploads`). Weekly at minimum.

## 9. If the shared hosting cannot run it

If PHP 8.3+ is unavailable, or there is no folder outside `public_html`, do **not** downgrade Laravel or Filament. Use a small VPS as described in `docs/VPS_REQUIREMENTS.md`.

As a last resort, the whole app can live inside `public_html/mikrotik-app` *only if* that folder is blocked with an `.htaccess` `Require all denied` and the web root is `public_html/mikrotik-app/public`. This is not recommended.
