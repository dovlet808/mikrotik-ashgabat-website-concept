#!/usr/bin/env bash
# Disposable QA copy of the local database, for testing admin write workflows without touching `mikrotik`.
#
#   tools/qa/qa-db.sh clone   # (re)create mikrotik_qa from mikrotik and anonymise copied inquiries
#   tools/qa/qa-db.sh drop    # remove mikrotik_qa
#
# Serve the copy on its own port (never `artisan serve`, which may drop the DB override):
#   cd public && DB_DATABASE=mikrotik_qa APP_URL=http://127.0.0.1:8096 SESSION_COOKIE=mikrotik_qa_session \
#     php -S 127.0.0.1:8096 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
#
# The root password stays inside the container ($MARIADB_ROOT_PASSWORD); it is never printed.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
CONTAINER="${DB_CONTAINER:-mikrotik-db}"
SRC="${SRC_DB:-mikrotik}"
QA="${QA_DB:-mikrotik_qa}"

[[ "$QA" != "$SRC" && "$QA" == *_qa ]] || { echo "refusing: QA database must end in _qa and differ from $SRC" >&2; exit 1; }

sql() { docker exec "$CONTAINER" sh -c "mariadb -uroot -p\"\$MARIADB_ROOT_PASSWORD\" -e \"$1\""; }

case "${1:-}" in
  clone)
    sql "DROP DATABASE IF EXISTS \\\`$QA\\\`; CREATE DATABASE \\\`$QA\\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON \\\`$QA\\\`.* TO 'mikrotik'@'%'; FLUSH PRIVILEGES;"
    docker exec "$CONTAINER" sh -c "mariadb-dump -uroot -p\"\$MARIADB_ROOT_PASSWORD\" --single-transaction --routines --triggers $SRC | mariadb -uroot -p\"\$MARIADB_ROOT_PASSWORD\" $QA"
    (cd "$ROOT" && DB_DATABASE="$QA" php artisan tinker --execute='
      if (DB::connection()->getDatabaseName() !== env("DB_DATABASE")) { echo "wrong database\n"; exit(1); }
      $n = 0;
      foreach (App\Models\Inquiry::query()->orderBy("id")->get() as $i) {
          $n++;
          $i->forceFill(["name" => "Демо-клиент (копия) ".$n, "phone" => "+993 00 0000".str_pad((string) $n, 2, "0", STR_PAD_LEFT), "email" => null, "ip_hash" => str_repeat("0", 64), "user_agent" => "qa-clone"])->saveQuietly();
      }
      DB::table("sessions")->delete();
      app(App\Support\Settings::class)->save(["address" => ["ru" => "QA-CLONE-MARKER"]]);
      echo "cloned into ".DB::connection()->getDatabaseName().": ".App\Models\Product::count()." products, ".$n." inquiries anonymised\n";')
    ;;
  drop)
    sql "DROP DATABASE IF EXISTS \\\`$QA\\\`;"
    echo "dropped $QA"
    ;;
  *)
    echo "usage: $0 clone|drop" >&2; exit 2 ;;
esac
