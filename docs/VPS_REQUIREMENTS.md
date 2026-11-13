# VPS requirements (fallback if shared hosting is unsuitable)

Use a VPS if the DirectAdmin plan cannot provide PHP ≥ 8.3, a folder outside `public_html`, or the required PHP extensions. Any provider with an Ubuntu image works; a server located in Turkmenistan gives the fastest load times for local visitors.

## Size

| Resource | Minimum | Comfortable |
|---|---|---|
| vCPU | 1 | 2 |
| RAM | 1 GB (+1 GB swap) | 2 GB |
| Disk | 15 GB SSD | 25 GB SSD (photos, backups) |
| Traffic | low: a catalogue site with WebP images | — |
| OS | Ubuntu 24.04 LTS | Ubuntu 24.04 LTS |
| IP | 1 public IPv4 | + reverse DNS for mail |

## Software

- nginx 1.24+ (or Apache 2.4 with `AllowOverride All`)
- PHP **8.4** FPM with: `bcmath ctype curl dom exif fileinfo gd intl mbstring mysql opcache readline tokenizer xml zip`. On Ubuntu: `apt install php8.4-fpm php8.4-{bcmath,curl,gd,intl,mbstring,mysql,xml,zip}`, or use the ondrej/php PPA if 8.4 is not in the default repos.
- MariaDB 10.11+ or MySQL 8.0+
- Composer 2
- certbot (Let's Encrypt)
- Node.js is **not** required on the server (assets are built locally and uploaded)
- ufw with ports 22, 80 and 443 open; fail2ban for SSH

## Layout

```
/var/www/mikrotik/                 Laravel root (owner deploy:www-data)
/var/www/mikrotik/public           nginx document root
/var/www/mikrotik/.env             chmod 640, not in git
```

The standard `public/index.php` is used; `APP_PUBLIC_PATH` is not needed on a VPS.

## nginx

A ready server block is in `deployment/vps/nginx-mikrotik.conf`. It sets:

- the document root to `public/` and the `try_files` front controller;
- gzip, long caching for `/build/assets` and `/uploads`;
- denial of dotfiles and of script execution in `/uploads`;
- `client_max_body_size 10m`.

## Deploy steps

```bash
# once
sudo mkdir -p /var/www/mikrotik && sudo chown deploy:www-data /var/www/mikrotik
# each release (from the build machine)
./deployment/build-release.sh
rsync -a dist/mikrotik-release-*/app/        deploy@VPS:/var/www/mikrotik/ --exclude .env --exclude 'storage/logs/*' --exclude 'storage/framework/sessions/*'
rsync -a dist/mikrotik-release-*/public_html/ deploy@VPS:/var/www/mikrotik/public/ --exclude uploads
# on the VPS — use Laravel's standard front controller (the release ships it as index.vps.php)
cd /var/www/mikrotik
mv public/index.vps.php public/index.php
php artisan migrate --force && php artisan db:seed --force      # first time only for the seed
php artisan app:create-admin --email=owner@… --generate          # first time only
php artisan optimize
sudo chown -R deploy:www-data storage bootstrap/cache public/uploads && sudo chmod -R 775 storage bootstrap/cache public/uploads
```

On a VPS use `index.vps.php` (renamed to `index.php`), not the DirectAdmin `index.php`, which expects the app outside `public_html`.

## Operations

- **Backups:** nightly `mysqldump` plus `public/uploads`, 14-day retention, copied off-server.
- **Cron:** not required (no scheduler or queues). Optional: `0 3 * * * php /var/www/mikrotik/artisan model:prune`.
- **Updates:** `apt unattended-upgrades` for security patches, and a monthly `composer update` on a staging copy first.
- **Monitoring:** `/up` health endpoint (HTTP 200) for an uptime checker.
- **Mail:** an SMTP relay (provider SMTP or a transactional mail service) for inquiry notifications.

## Cost of ownership

- VPS rental (monthly) and domain renewal.
- Maintenance: updates, backups, monitoring.
