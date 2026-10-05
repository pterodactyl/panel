#!/bin/ash
set -eu
cd /app
umask 077

# Remove bootstrap snapshots before Laravel boots with this container's environment.
# optimize:clear also flushes the shared application cache, so do not use it here.
php -r '
  require "vendor/autoload.php";
  $app = require "bootstrap/app.php";
  foreach ([$app->getCachedConfigPath(), $app->getCachedRoutesPath(), $app->getCachedEventsPath()] as $path) {
    if (is_file($path) && !unlink($path)) {
      exit(1);
    }
  }
'
php artisan view:clear --no-interaction
php artisan package:discover --no-interaction

case "$LARAVEL_OPTIMIZE" in
  false) exit 0 ;;
  true) ;;
  *) echo 'LARAVEL_OPTIMIZE must be true or false.' >&2; exit 1 ;;
esac

mode=$(php -r '
  require "vendor/autoload.php";
  $app = require "bootstrap/app.php";
  $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
  echo config("extensions.enabled") ? "dynamic" : (config("pterodactyl.load_environment_only") ? "full" : "database-settings");
')

# Extension providers and database settings mutate configuration during boot.
# Freeze only snapshots whose inputs cannot change while this container is running.
if [ "$mode" = full ]; then
  php artisan config:cache --no-interaction
fi
if [ "$mode" != dynamic ]; then
  php artisan event:cache --no-interaction
  php artisan route:cache --no-interaction
fi
php artisan view:cache --no-interaction
printf 'Laravel deployment caches prepared (%s).\n' "$mode"
