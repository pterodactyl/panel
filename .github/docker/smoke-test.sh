#!/usr/bin/env bash
set -euo pipefail
trap 'echo "Smoke test failed at line $LINENO." >&2' ERR

image=${1:?Usage: smoke-test.sh IMAGE}
script_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
prefix="panel-smoke-$$"
app="$prefix-app"
tls="$prefix-tls"
dynamic="$prefix-dynamic"
database="$prefix-db"
redis="$prefix-redis"
workdir=$(mktemp -d)

cleanup() {
  status=$?
  if [ "$status" -ne 0 ]; then
    docker logs --tail 60 "$app" 2>/dev/null || true
    docker logs --tail 30 "$tls" 2>/dev/null || true
    docker logs --tail 40 "$dynamic" 2>/dev/null || true
    cat "$workdir/docs.log" 2>/dev/null | tail -n 30 || true
  fi
  docker rm -fv "$app" "$tls" "$dynamic" "$database" "$redis" >/dev/null 2>&1 || true
  docker network rm "$prefix" >/dev/null 2>&1 || true
  rm -rf "$workdir"
  exit "$status"
}
trap cleanup EXIT

wait_for() {
  for ((attempt=0; attempt<90; attempt++)); do
    if "$@" > "$workdir/wait.log" 2>&1; then
      return 0
    fi
    sleep 1
  done
  echo "Timed out: $*" >&2
  cat "$workdir/wait.log" >&2
  return 1
}

healthy() {
  docker exec "$1" /bin/ash /app/.github/docker/healthcheck.sh >/dev/null 2>&1
}

run_php() {
  docker exec -i "$app" php
}

docker network create "$prefix" >/dev/null
docker run -d --name "$database" --network "$prefix" \
  -e MARIADB_RANDOM_ROOT_PASSWORD=yes -e MARIADB_DATABASE=panel \
  -e MARIADB_USER=panel -e MARIADB_PASSWORD=smoke-test-only mariadb:11.4 >/dev/null
docker run -d --name "$redis" --network "$prefix" redis:7.4-alpine >/dev/null
wait_for docker exec "$database" healthcheck.sh --connect --innodb_initialized
wait_for docker exec "$redis" redis-cli ping

environment=(
  -e APP_ENVIRONMENT_ONLY=true -e APP_URL=http://panel.example -e APP_TIMEZONE=UTC
  -e APP_KEY=base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=
  -e HASHIDS_SALT=smoke-test-only -e DB_HOST="$database" -e DB_DATABASE=panel
  -e DB_USERNAME=panel -e DB_PASSWORD=smoke-test-only -e CACHE_STORE=redis
  -e SESSION_DRIVER=redis -e SESSION_CONNECTION=sessions -e QUEUE_CONNECTION=redis -e REDIS_HOST="$redis"
  -e PTERODACTYL_EXTENSIONS_ENABLED=false
  -e MAIL_MAILER=array
)

docker run -d --name "$app" --network "$prefix" "${environment[@]}" "$image" >/dev/null
wait_for healthy "$app"
docker exec "$app" supervisorctl stop scheduler >/dev/null
if docker exec "$app" /bin/ash /app/.github/docker/healthcheck.sh >/dev/null 2>&1; then
  echo 'Health check accepted a stopped scheduler.' >&2
  exit 1
fi
docker exec "$app" supervisorctl start scheduler >/dev/null
# Starting a replica must not create the schema.
tables=$(docker exec -e MYSQL_PWD=smoke-test-only "$database" \
  mariadb -upanel -N -e 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema="panel"')
test "$tables" = 0

docker exec "$app" php artisan migrate --seed --force --isolated=1 --no-interaction > "$workdir/migrations.log"
docker exec "$app" /bin/ash -ec '
  test -s bootstrap/cache/config.php
  test -s bootstrap/cache/routes-v7.php
  test -s bootstrap/cache/events.php
  set -- storage/framework/views/*.php
  test -f "$1"
  php-fpm -t
  nginx -t
  composer check-platform-reqs --no-dev
  test ! -d vendor/pestphp
  test ! -d vendor/phpstan
  test ! -d vendor/laravel/boost
  test -s public/assets/manifest.json
  test -s public/assets/svgs/pterodactyl.svg
  curl -fsS -c /tmp/smoke-cookies http://127.0.0.1/auth/login >/dev/null
'
test "$(docker exec "$redis" redis-cli -n 1 dbsize)" -gt 0
run_php <<'PHP'
<?php
require '/app/vendor/autoload.php';
$app = require '/app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Cache::put('docker-smoke-cache', 'ok', 60);
if (Illuminate\Support\Facades\Cache::get('docker-smoke-cache') !== 'ok') {
    exit(1);
}
PHP

docker exec "$app" composer docs:openapi:verify > "$workdir/docs.log" 2>&1
tail -n 2 "$workdir/docs.log"

# Exercise the installed scheduler as its configured Unix user.
docker exec "$app" /bin/ash -ec '
  cp /var/spool/cron/crontabs/nginx /tmp/smoke-crontab
  cp /tmp/smoke-crontab /tmp/smoke-crontab-updated
  echo "* * * * * id > /app/storage/logs/cron-smoke" >> /tmp/smoke-crontab-updated
  crontab -u nginx /tmp/smoke-crontab-updated
'
wait_for docker exec "$app" test -s /app/storage/logs/cron-smoke
docker exec "$app" grep -q '(nginx)' /app/storage/logs/cron-smoke
docker exec "$app" crontab -u nginx /tmp/smoke-crontab

# A real queued closure must finish during a graceful container stop.
docker exec -i "$app" /bin/ash -c 'cat > /tmp/smoke-queue.php' <<'PHP'
<?php
require '/app/vendor/autoload.php';
$app = require '/app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Queue::push(Illuminate\Queue\CallQueuedClosure::create(function () {
    file_put_contents('/app/storage/logs/queue-started', 'started');
    $deadline = microtime(true) + 8;
    while (microtime(true) < $deadline) {
        usleep(100000);
    }
    file_put_contents('/app/storage/logs/queue-finished', 'finished');
}));
PHP
docker exec "$app" php /tmp/smoke-queue.php
wait_for docker exec "$app" test -s /app/storage/logs/queue-started
docker exec "$app" /bin/ash -ec 'sha256sum /app/var/.env > /tmp/smoke-env-checksum'
docker exec "$redis" redis-cli set smoke-preserved yes >/dev/null
docker exec "$app" /bin/ash -ec 'echo "APP_NAME=restart-cache-probe" >> /app/var/.env; sha256sum /app/var/.env > /tmp/smoke-env-checksum'
docker stop --time 90 "$app" >/dev/null
test "$(docker inspect -f '{{.State.ExitCode}}' "$app")" = 0
docker cp "$app:/app/storage/logs/queue-finished" "$workdir/queue-finished"
grep -qx finished "$workdir/queue-finished"
docker start "$app" >/dev/null
wait_for healthy "$app"
docker exec "$app" /bin/ash -ec 'sha256sum -c /tmp/smoke-env-checksum; test -L /var/log/panel/logs'

docker exec "$app" php -r '$config = require "bootstrap/cache/config.php"; exit($config["app"]["name"] === "restart-cache-probe" ? 0 : 1);'
test "$(docker exec "$redis" redis-cli get smoke-preserved)" = yes

# Dynamic settings and extension state must remain live with optimization enabled.
docker run -d --name "$dynamic" --network "$prefix" "${environment[@]}" \
  -e APP_ENVIRONMENT_ONLY=false -e PTERODACTYL_EXTENSIONS_ENABLED=true \
  -e QUEUE_WORKER_ENABLED=false -e SCHEDULER_ENABLED=false \
  -e PHP_FPM_MAX_CHILDREN=3 -e PHP_OPCACHE_MEMORY_CONSUMPTION=192 -e PHP_OPCACHE_REVALIDATE_FREQ=5 \
  "$image" >/dev/null
wait_for healthy "$dynamic"
docker exec "$dynamic" /bin/ash -ec '
  test ! -f bootstrap/cache/config.php
  test ! -f bootstrap/cache/routes-v7.php
  test ! -f bootstrap/cache/events.php
  php-fpm -tt 2>&1 | grep -q "pm.max_children = 3"
  php -r '\''exit(ini_get("opcache.memory_consumption") === "192" ? 0 : 1);'\''
  php -r '\''exit(ini_get("opcache.revalidate_freq") === "5" ? 0 : 1);'\''
'
for name in 'First live name' 'Second live name'; do
  docker exec -i -e SMOKE_NAME="$name" "$dynamic" php <<'PHP'
<?php
require '/app/vendor/autoload.php';
$app = require '/app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Pterodactyl\Models\Setting::put('settings::app:name', getenv('SMOKE_NAME'));
PHP
  docker exec "$dynamic" curl -fsS http://127.0.0.1/auth/login | grep -q "$name"
done

docker exec "$dynamic" mkdir -p /app/extensions/cache-probe/src /app/extensions/cache-probe/dist
docker exec -i "$dynamic" /bin/ash -c 'cat > /app/extensions/cache-probe/extension.json' <<'JSON'
{"id":"cache-probe","name":"Cache probe","version":"1.0.0","provider":"CacheProbe\\Provider","autoload":{"CacheProbe\\":"src/"},"ui":{"entry":"dist/client.js"}}
JSON
docker exec -i "$dynamic" /bin/ash -c 'cat > /app/extensions/cache-probe/src/Provider.php' <<'PHP'
<?php
namespace CacheProbe;
class Provider extends \Pterodactyl\Extensions\ExtensionProvider
{
    public function boot(): void
    {
        \Illuminate\Support\Facades\Route::get('/api/cache-extension-probe', fn () => 'extension-enabled');
    }
}
PHP
docker exec "$dynamic" /bin/ash -ec 'echo "export const smoke = true;" > /app/extensions/cache-probe/dist/client.js'
for enabled in true false true; do
  docker exec -i --user nginx -e SMOKE_ENABLED="$enabled" "$dynamic" php <<'PHP'
<?php
require '/app/vendor/autoload.php';
$app = require '/app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(Pterodactyl\Contracts\Extensions\SetsExtensionEnabled::class)
    ->setEnabled('cache-probe', getenv('SMOKE_ENABLED') === 'true');
PHP
  if [ "$enabled" = true ]; then
    docker exec "$dynamic" curl -fsS http://127.0.0.1/api/cache-extension-probe | grep -qx extension-enabled
    version=$(docker exec "$dynamic" cat /app/public/assets/extensions/cache-probe/_current)
    [[ "$version" =~ ^[a-f0-9]{64}$ ]]
    docker exec "$dynamic" curl -fsS "http://127.0.0.1/assets/extensions/cache-probe/$version/client.js" | grep -qx "export const smoke = true;"
  else
    test "$(docker exec "$dynamic" curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/api/cache-extension-probe)" = 404
  fi
done

# Check the preserved custom-config path with a disposable trusted certificate.
mkdir -p "$workdir/certs/live/panel.example"
openssl req -x509 -newkey rsa:2048 -nodes -days 1 -subj /CN=panel.example \
  -addext subjectAltName=DNS:panel.example \
  -keyout "$workdir/certs/live/panel.example/privkey.pem" \
  -out "$workdir/certs/live/panel.example/fullchain.pem" > "$workdir/openssl.log" 2>&1
sed 's/<domain>/panel.example/g' "$script_dir/default_ssl.conf" > "$workdir/panel.conf"
docker create --name "$tls" --network "$prefix" "${environment[@]}" \
  -e APP_URL=https://panel.example -e LE_EMAIL=smoke@example.test \
  -e QUEUE_WORKER_ENABLED=false -e SCHEDULER_ENABLED=false \
  "$image" >/dev/null
docker cp "$workdir/certs/." "$tls:/etc/letsencrypt/"
docker cp "$workdir/panel.conf" "$tls:/etc/nginx/http.d/panel.conf"
docker start "$tls" >/dev/null
wait_for healthy "$tls"
for version in 1.2 1.3; do
  docker exec "$tls" curl -fsS --tlsv"$version" --tls-max "$version" \
    --cacert /etc/letsencrypt/live/panel.example/fullchain.pem \
    --resolve panel.example:443:127.0.0.1 https://panel.example/auth/login >/dev/null
done
# Regenerate the bundled TLS config using the existing certificate, without ACME traffic.
docker exec "$tls" rm /etc/nginx/http.d/panel.conf
docker restart --time 90 "$tls" >/dev/null
wait_for healthy "$tls"
docker exec "$tls" curl -fsS \
  --cacert /etc/letsencrypt/live/panel.example/fullchain.pem \
  --resolve panel.example:443:127.0.0.1 https://panel.example/auth/login >/dev/null
docker exec "$tls" /bin/ash -ec '
  supervisorctl status queue-worker | grep -q STOPPED
  supervisorctl status scheduler | grep -q STOPPED
'
echo 'PASS: no startup migrations; Redis sessions/cache/queue; docs; scheduler; graceful stop; restart; TLS 1.2/1.3; service toggles; deployment caches; live settings/extensions.'
