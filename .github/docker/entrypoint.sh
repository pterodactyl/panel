#!/bin/ash
set -eu
cd /app

mkdir -p /app/var /app/extensions /app/public/assets/extensions /app/storage/logs /app/storage/framework/cache/data \
  /app/storage/framework/sessions /app/storage/framework/views /app/bootstrap/cache \
  /var/log/panel /var/log/supervisord /var/log/nginx
ln -sfn /app/storage/logs /var/log/panel/logs

# Serialize first-start key creation when containers share the persistent volume.
(
  flock -x 9
  if [ ! -f /app/var/.env ]; then
    umask 027
    php -r '
      $values = [
        "APP_KEY" => getenv("APP_KEY") ?: "base64:".base64_encode(random_bytes(32)),
        "HASHIDS_SALT" => getenv("HASHIDS_SALT") ?: bin2hex(random_bytes(32)),
      ];
      $contents = "";
      foreach ($values as $name => $value) {
        $contents .= $name."=\"".addcslashes($value, "\\\"\$\r\n")."\"\n";
      }
      if (file_put_contents("/app/var/.env.tmp", $contents) === false || !rename("/app/var/.env.tmp", "/app/var/.env")) {
        exit(1);
      }
    '
    chown nginx:nginx /app/var/.env
  fi
) 9>/app/var/.env.lock
ln -sfn /app/var/.env /app/.env
chown -R nginx:nginx /app/storage /app/bootstrap/cache /app/extensions /app/public/assets/extensions

# One-off release and documentation commands use the same persisted environment.
# Migrations are deliberately explicit: never change the schema on replica startup.
if [ "${1:-}" != "supervisord" ]; then
  exec "$@"
fi

for enabled in "$QUEUE_WORKER_ENABLED" "$SCHEDULER_ENABLED"; do
  case "$enabled" in
    true|false) ;;
    *) echo 'QUEUE_WORKER_ENABLED and SCHEDULER_ENABLED must be true or false.' >&2; exit 1 ;;
  esac
done

if [ ! -f /etc/nginx/http.d/panel.conf ]; then
  if [ -n "${LE_EMAIL:-}" ]; then
    domain=$(php -r '
      $url = parse_url(getenv("APP_URL") ?: "");
      $host = $url["host"] ?? "";
      if (($url["scheme"] ?? "") !== "https" || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        fwrite(STDERR, "LE_EMAIL requires an https APP_URL with a valid hostname.\n");
        exit(1);
      }
      echo $host;
    ')
    if [ ! -s "/etc/letsencrypt/live/$domain/fullchain.pem" ] || [ ! -s "/etc/letsencrypt/live/$domain/privkey.pem" ]; then
      certbot certonly --standalone --non-interactive --agree-tos --email "$LE_EMAIL" --domains "$domain"
    fi
    sed "s|<domain>|$domain|g" .github/docker/default_ssl.conf > /etc/nginx/http.d/panel.conf.tmp
  else
    cp .github/docker/default.conf /etc/nginx/http.d/panel.conf.tmp
  fi
  mv /etc/nginx/http.d/panel.conf.tmp /etc/nginx/http.d/panel.conf
fi

# Configs persisted by older images hand files under /assets/ to PHP-FPM; serve that tree as static files only.
if ! grep -qF 'location ^~ /assets/' /etc/nginx/http.d/panel.conf; then
  ASSETS_BLOCK=$(cat <<'EOF'
    location ^~ /assets/ {
        location ~ /\. {
            deny all;
        }

        try_files $uri =404;
    }
EOF
) awk '!done && index($0, "location ~ \\.php$") { print ENVIRON["ASSETS_BLOCK"]; print ""; done = 1 } { print }' \
    /etc/nginx/http.d/panel.conf > /etc/nginx/http.d/panel.conf.tmp
  mv /etc/nginx/http.d/panel.conf.tmp /etc/nginx/http.d/panel.conf
  grep -qF 'location ^~ /assets/' /etc/nginx/http.d/panel.conf \
    || echo 'warning: /etc/nginx/http.d/panel.conf has no PHP location; add the /assets/ block from .github/docker/default.conf by hand.' >&2
fi

# Extension files opened directly must not run script as the panel. Its add_header replaces
# any server-level headers for these responses.
if ! grep -qF 'location ^~ /assets/extensions/' /etc/nginx/http.d/panel.conf; then
  EXTENSION_ASSETS_BLOCK=$(cat <<'EOF'
    location ^~ /assets/extensions/ {
        add_header Content-Security-Policy "sandbox" always;
        add_header X-Content-Type-Options nosniff always;

        location ~ /\. {
            deny all;
        }

        location ~ \.m?js$ {
            add_header X-Content-Type-Options nosniff always;
            try_files $uri =404;
        }

        try_files $uri =404;
    }
EOF
) awk '!done && index($0, "location ~ \\.php$") { print ENVIRON["EXTENSION_ASSETS_BLOCK"]; print ""; done = 1 } { print }' \
    /etc/nginx/http.d/panel.conf > /etc/nginx/http.d/panel.conf.tmp
  mv /etc/nginx/http.d/panel.conf.tmp /etc/nginx/http.d/panel.conf
  grep -qF 'location ^~ /assets/extensions/' /etc/nginx/http.d/panel.conf \
    || echo 'warning: /etc/nginx/http.d/panel.conf has no PHP location; add the /assets/extensions/ block from .github/docker/default.conf by hand.' >&2
fi
rm -f /etc/nginx/http.d/default.conf
cp .github/docker/health.conf /etc/nginx/http.d/health.conf

# Catch configuration and application boot failures before starting background services.
nginx -t
php-fpm -t
su-exec nginx /bin/ash /app/.github/docker/optimize.sh
exec "$@"
