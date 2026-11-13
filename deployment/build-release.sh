#!/usr/bin/env bash
# Builds an upload-ready release for DirectAdmin shared hosting:
#   dist/mikrotik-release-<stamp>/app/          -> upload to /home/USER/mikrotik-app/
#   dist/mikrotik-release-<stamp>/public_html/  -> upload into the demo domain's public_html/
# plus a .zip of both.
#
# The application side is built from an explicit list of runtime paths (APP_PATHS), not by copying
# the project and excluding what should stay behind: new local folders (screenshots, review PDFs,
# backups, credentials) therefore never reach a release by accident. The finished zip is verified
# at the end and the build fails if anything private or anything required is out of place.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
STAMP="$(date +%Y%m%d-%H%M)"
NAME="mikrotik-release-$STAMP"
OUT="$ROOT/dist/$NAME"
ZIP="$ROOT/dist/$NAME.zip"

# Everything the Laravel app needs on the server. media/products holds the product photo masters
# that `php artisan db:seed` copies into public_html/uploads on first install; it is optional because
# the masters are kept out of the repository.
APP_PATHS=(app bootstrap config database lang resources routes storage artisan composer.json composer.lock)
[[ -d "$ROOT/media/products" ]] && APP_PATHS+=(media/products)

# Never shipped, wherever they appear inside the copied paths.
COMMON_EXCLUDES=(
  --exclude '.git' --exclude '.gitignore' --exclude '.gitattributes' --exclude '.DS_Store'
  --exclude '.env' --exclude '.env.*' --exclude '*.log' --exclude '*.sql' --exclude '*.sql.gz'
  --exclude '*.sqlite' --exclude '*.sqlite3' --exclude '*.dump' --exclude '*.bak' --exclude '*.swp'
  --exclude '.phpunit.result.cache' --exclude '.phpunit.cache' --exclude 'node_modules'
)

echo "→ Building front-end assets"
(cd "$ROOT" && npm ci --no-audit --no-fund && npm run build)

echo "→ Copying application to $OUT/app"
mkdir -p "$OUT/app" "$OUT/public_html"
(cd "$ROOT" && rsync -a --relative "${COMMON_EXCLUDES[@]}" \
  --exclude '/bootstrap/cache/*.php' \
  --exclude '/storage/logs/*' --exclude '/storage/framework/cache/data/*' \
  --exclude '/storage/framework/sessions/*' --exclude '/storage/framework/views/*' \
  --exclude '/storage/framework/testing' --exclude '/storage/app/private/*' --exclude '/storage/app/public/*' \
  "${APP_PATHS[@]/#/./}" "$OUT/app/")

echo "→ Installing production PHP dependencies"
(cd "$OUT/app" && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --quiet --no-scripts && php artisan package:discover --ansi >/dev/null)
rm -rf "$OUT/app/public"

echo "→ Preparing public_html"
rsync -a "${COMMON_EXCLUDES[@]}" "$ROOT/public/" "$OUT/public_html/" \
  --exclude 'hot' --exclude 'uploads/*' --exclude 'index.php' --exclude '.htaccess' --exclude 'storage'
mkdir -p "$OUT/public_html/uploads"
cp "$ROOT/public/uploads/.htaccess" "$OUT/public_html/uploads/.htaccess"
cp "$ROOT/deployment/directadmin/index.php" "$OUT/public_html/index.php"
cp "$ROOT/public/index.php" "$OUT/public_html/index.vps.php"   # standard Laravel front controller for VPS layouts
cp "$ROOT/deployment/directadmin/.htaccess" "$OUT/public_html/.htaccess"
cp "$ROOT/deployment/directadmin/env.production.example" "$OUT/app/.env.production.example"

echo "→ Zipping"
(cd "$ROOT/dist" && zip -qr "$NAME.zip" "$NAME")

echo "→ Verifying the archive"
LISTING="$(unzip -Z1 "$ZIP")"
# Project folders are checked at the app root only (vendor packages may legitimately contain e.g. "tests/").
ROOT_FORBIDDEN="^$NAME/app/(tests|docs|tools|dist|source|node_modules|design-system|deployment|media/generated|client-presentation|visual-review|visual-screenshots|bugs|\.run|\.git)(/|$)"
STORAGE_FORBIDDEN="^$NAME/app/storage/(logs|framework/sessions|framework/views|framework/testing|framework/cache/data|app/private|app/public)/.+"
PUBLIC_FORBIDDEN="^$NAME/public_html/(hot|storage|uploads/[^.].*)$"
ANYWHERE_FORBIDDEN='(^|/)(\.env|\.env\.(local|testing|backup|bak|prod|production))$|(^|/)(\.git|\.run|visual-review|visual-screenshots)(/|$)|admin-credentials|\.(sql|sql\.gz|sqlite|sqlite3|dump|bak)$|(^|/)\.phpunit'
if BAD="$(grep -E "$ROOT_FORBIDDEN|$STORAGE_FORBIDDEN|$PUBLIC_FORBIDDEN|$ANYWHERE_FORBIDDEN" <<<"$LISTING")"; then
  echo "✗ Forbidden paths in the archive:" >&2
  echo "$BAD" >&2
  exit 1
fi
for required in app/artisan app/composer.json app/vendor/autoload.php app/bootstrap/app.php app/config/app.php \
  app/routes/web.php app/database/data/catalog.php app/.env.production.example \
  public_html/index.php public_html/.htaccess public_html/build/manifest.json public_html/uploads/.htaccess; do
  grep -qE "^$NAME/$required/?$" <<<"$LISTING" || { echo "✗ Missing from the archive: $required" >&2; exit 1; }
done
if [[ -f "$ROOT/.env" ]]; then
  while IFS='=' read -r key value; do
    value="${value%\"}"; value="${value#\"}"
    [[ "$key" =~ ^(APP_KEY|DB_PASSWORD|MAIL_PASSWORD|AWS_SECRET_ACCESS_KEY|REDIS_PASSWORD)$ && ${#value} -ge 8 && "$value" != "null" ]] || continue
    if grep -rqF -- "$value" "$OUT"; then
      echo "✗ The local value of $key appears inside the release" >&2
      exit 1
    fi
  done < "$ROOT/.env"
fi
echo "✓ Archive verified: $(wc -l <<<"$LISTING") entries, no private paths, required files present, no local secrets"

du -sh "$OUT/app" "$OUT/public_html" "$ZIP"
echo "Done. See docs/DEPLOYMENT_DIRECTADMIN.md for the upload steps."
