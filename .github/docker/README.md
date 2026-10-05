# Pterodactyl Panel Docker image

The image includes PHP 8.4, Nginx, a queue worker, a supervised scheduler, and Composer with production dependencies. Node is used only to build frontend assets. Scribe and its required dependencies are included so API attributes and documentation commands work with `--no-dev`.

## Install and upgrade

Use `docker-compose.example.yml` as your Compose file. Set the database password, application URL, and mail configuration before starting. Use a versioned image tag or digest for controlled production upgrades.

Start the dependencies, then run migrations as an explicit release step:

```sh
docker compose up -d --wait database cache
docker compose run --rm panel php artisan migrate --seed --force --isolated=1 --no-interaction
docker compose up -d --wait panel
docker compose exec panel php artisan p:user:make
```

For upgrades, back up the database and `/app/var/.env`, pull the selected image, stop the panel, run the migration command once, and start the panel again. Migrations and seeders never run automatically on container startup. A failed or competing migration exits nonzero; resolve it before starting the new release. Isolation requires the shared Redis cache configured in the example.

Application commands can also be run with `docker exec`, or through the image entrypoint with `docker run IMAGE php artisan ...`. One-off commands do not start web services or request certificates.

## Persistent data and secrets

| Container path | Purpose |
| --- | --- |
| `/app/var` | Application key, Hashids salt, and optional custom `.env` |
| `/etc/nginx/http.d` | Nginx configuration |
| `/etc/letsencrypt` | Certificates and renewal configuration |
| `/app/storage/logs` | Application and scheduled-task logs |
| `/app/extensions` | Installed extensions |
| `/app/public/assets/extensions` | Published extension browser assets; persist alongside installed extensions |

The first start atomically creates `/app/var/.env` with cryptographically random secrets unless that file already exists. Environment variables override file values. Supplied `APP_KEY` and `HASHIDS_SALT` are persisted on first creation. Keep those values stable across upgrades; losing the application key makes existing encrypted data unreadable. Container logs never print these secrets.

Use the same `APP_KEY`, `HASHIDS_SALT`, database, Redis, and extension files for every replica. Set `SCHEDULER_ENABLED=false` on all but one replica to avoid duplicate scheduled work. Persistent filesystem volumes must support file locking and Unix ownership. Application commands run as root by default; use `docker compose exec --user nginx panel ...` for normal application maintenance where possible.

## Environment

| Variable | Purpose / default |
| --- | --- |
| `APP_URL` | Public URL, including scheme |
| `APP_TIMEZONE` | Application timezone, normally `UTC` |
| `APP_KEY`, `HASHIDS_SALT` | Stable shared secrets; generated on first start if absent |
| `APP_ENV` / `APP_DEBUG` | Image defaults: `production` / `false` |
| `APP_ENVIRONMENT_ONLY` | Set `true` to manage application settings through environment variables |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | MySQL/MariaDB connection |
| `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION` | Use `redis` for the example deployment |
| `SESSION_CONNECTION` | Set `sessions` to use the separate Redis session database |
| `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` | Shared Redis connection |
| `REDIS_QUEUE_RETRY_AFTER` | Must exceed the worker's 60-second timeout; default `90` |
| `QUEUE_WORKER_ENABLED` | `true` (default) or `false` |
| `SCHEDULER_ENABLED` | `true` (default) or `false`; enable on exactly one replica |
| `MAIL_MAILER`, `MAIL_FROM_ADDRESS` | Mail transport and sender address |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` | SMTP settings |
| `LE_EMAIL` | Optional email for direct Let's Encrypt HTTPS |

The application also accepts the legacy `CACHE_DRIVER`, `QUEUE_DRIVER`, and `MAIL_DRIVER` names. The newer names take precedence. Use the configured mail transport's own requirements for non-SMTP mail.

## HTTPS

For direct Let's Encrypt HTTPS, set an `https://` `APP_URL` and `LE_EMAIL`, point DNS to this host, and expose ports 80 and 443. Initial issuance uses the standalone HTTP challenge before Nginx starts. Existing certificates are reused on restart; the supervised cron service runs renewal daily. Certificate renewal therefore needs `SCHEDULER_ENABLED=true` on the container managing those certificates.

For your own certificates, mount a `panel.conf` with the correct certificate paths. Existing `panel.conf` files are preserved, including on upgrades: update their TLS and location settings yourself using the bundled templates as a reference. New HTTPS configurations enable only TLS 1.2 and TLS 1.3.

Behind a TLS-terminating reverse proxy, leave `LE_EMAIL` unset, keep the public `https://` `APP_URL`, and forward requests to container port 80. Configure the application's trusted proxies for your proxy network. The image reserves `127.0.0.1:8080` internally for the Nginx/PHP health probe; it is not published.

## Health and shutdown

The health check verifies Nginx, PHP-FPM, and each enabled background service. It is a process/liveness check; monitor database and Redis availability separately. Supervisor sends graceful shutdown signals and allows the queue worker 70 seconds to finish a job. Keep Docker's stop timeout at least 90 seconds (`stop_grace_period: 90s` in Compose). Custom job timeouts must remain below the queue retry interval, and longer jobs require adjusting the worker and stop timeouts together.

Supervisor and PHP worker output go to container logs. Application and scheduler output remain under `/app/storage/logs`; arrange log rotation for the persisted directory.

## API documentation

Run generation against a disposable database with the panel schema, using the production image and its normal database/environment configuration:

```sh
docker compose run --rm -e PTERODACTYL_EXTENSIONS_ENABLED=false panel composer docs:openapi:verify
```

Scribe executes factories while generating response examples. Use an isolated documentation environment, not the live production database. The command generates the Blade documentation and OpenAPI/Postman files, then verifies route coverage and the client contract. Generated files live in the command's container unless you mount an output directory or copy them out before removing the container. The image does not embed documentation generated from a developer's database.

## Build

```sh
docker build -t pterodactyl-panel:local .
docker buildx build --platform linux/amd64,linux/arm64 -t REGISTRY/panel:VERSION --push .
```

BuildKit caches npm and Composer downloads. `.dockerignore` excludes local secrets, installed dependencies, storage data, and generated bundles while preserving static assets. The repository lockfiles determine package versions.

Run `bash .github/docker/smoke-test.sh pterodactyl-panel:local` to test the image with disposable MariaDB and Redis containers. The suite checks startup without migrations, Redis sessions/cache/queue processing, documentation generation, scheduler execution, graceful shutdown, restart, HTTPS, and disabled services. CI builds each platform on a native amd64 or arm64 runner, runs this suite against that image, pushes it by digest, and then joins both digests into one multi-platform tag.

## Laravel deployment caches and PHP sizing

Startup rebuilds Laravel caches as the application user after reading the deployment environment. It clears only framework files and compiled views, never Redis keys. These generated files stay inside each container; do not share `bootstrap/cache` between replicas. Configuration snapshots may contain secrets and are created with restrictive permissions.

| Deployment | Caches prepared |
| --- | --- |
| `APP_ENVIRONMENT_ONLY=true`, `PTERODACTYL_EXTENSIONS_ENABLED=false` | Configuration, events, routes, and views |
| Database-managed settings, extensions disabled | Events, routes, and views; settings continue loading dynamically |
| Extension system enabled (the application default) | Views; provider configuration, events, and routes remain dynamic |

The policy depends on whether the extension system is enabled, even if no extensions are currently installed, so installing or enabling one later cannot leave a stale route snapshot. Database-backed settings are never baked into configuration caches. View timestamp checks and OPcache timestamp validation stay enabled for live changes. Restart long-running queue workers when changing their configuration or extension code, as with any Laravel deployment.

Set `LARAVEL_OPTIMIZE=false` to disable deployment caching for diagnostics; startup still removes old snapshots. Changing `.env` requires a container restart; changing Compose environment variables requires recreating the container. One-off entrypoint commands do not rebuild caches. Run documentation generation in a fresh, isolated command container as described above.

`PHP_FPM_MAX_CHILDREN` (default `9`) and `PHP_FPM_MAX_REQUESTS` (default `200`) configure the existing on-demand pool. `PHP_OPCACHE_MEMORY_CONSUMPTION` controls shared OPcache memory in MiB (default `128`). OPcache retains comments for reflection/documentation and disables JIT. The image does not disable timestamp checks because extensions and framework cache files can change at runtime.

These are starting values, not workload-specific tuning. Size the worker pool from measured busy-worker memory and the container's memory limit, leaving room for the queue worker, scheduler, OPcache, and Nginx. Increase concurrency only after measuring request latency, CPU use, and memory under representative load. Recreate the container after changing PHP sizing variables.
