#!/usr/bin/env bash
# Clean demonstration database for client-facing screenshots and presentations.
#
#   tools/qa/demo-db.sh build   # (re)create mikrotik_demo: real catalogue, NO real inquiries, fictional demo inquiries only
#   tools/qa/demo-db.sh drop
#
# Serve it on its own port while presenting or taking screenshots:
#   cd public && DB_DATABASE=mikrotik_demo APP_URL=http://127.0.0.1:8095 SESSION_COOKIE=mikrotik_demo_session \
#     php -S 127.0.0.1:8095 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
#
# Copied from `mikrotik`: products, photos metadata, specifications, categories, services, settings, admin users.
# Emptied: inquiries, inquiry notes, sessions, cache, password-reset tokens, queued/failed jobs.
# Added: the three records of DemoInquirySeeder ("Демо-клиент 1–3", +993 00 00000x, "[ДЕМО]" messages).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
CONTAINER="${DB_CONTAINER:-mikrotik-db}"
SRC="${SRC_DB:-mikrotik}"
DEMO="${DEMO_DB:-mikrotik_demo}"

[[ "$DEMO" != "$SRC" && "$DEMO" == *_demo ]] || { echo "refusing: demo database must end in _demo and differ from $SRC" >&2; exit 1; }

sql() { docker exec "$CONTAINER" sh -c "mariadb -uroot -p\"\$MARIADB_ROOT_PASSWORD\" -e \"$1\""; }

case "${1:-}" in
  build)
    sql "DROP DATABASE IF EXISTS \\\`$DEMO\\\`; CREATE DATABASE \\\`$DEMO\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON \\\`$DEMO\\\`.* TO 'mikrotik'@'%'; FLUSH PRIVILEGES;"
    docker exec "$CONTAINER" sh -c "mariadb-dump -uroot -p\"\$MARIADB_ROOT_PASSWORD\" --single-transaction --routines --triggers $SRC | mariadb -uroot -p\"\$MARIADB_ROOT_PASSWORD\" $DEMO"
    (cd "$ROOT" && DB_DATABASE="$DEMO" php artisan tinker --execute='
      if (DB::connection()->getDatabaseName() !== env("DB_DATABASE")) { echo "wrong database\n"; exit(1); }
      foreach (["inquiry_notes", "inquiries", "sessions", "cache", "cache_locks", "password_reset_tokens", "jobs", "job_batches", "failed_jobs"] as $table) {
          if (Schema::hasTable($table)) { DB::table($table)->delete(); }
      }
      Illuminate\Database\Eloquent\Model::unguarded(fn () => (new Database\Seeders\DemoInquirySeeder)->run());
      echo "built ".DB::connection()->getDatabaseName().": ".App\Models\Product::count()." products, ".App\Models\Inquiry::count()." fictional inquiries: ".App\Models\Inquiry::pluck("name")->implode(", ")."\n";')
    ;;
  drop)
    sql "DROP DATABASE IF EXISTS \\\`$DEMO\\\`;"
    echo "dropped $DEMO"
    ;;
  *)
    echo "usage: $0 build|drop" >&2; exit 2 ;;
esac
