#!/bin/ash
## Ensure we are in /app

cd /app

## check for .env file and generate app keys if missing
if [ -f /app/var/.env ]; then
  echo "external vars exist"
  rm /app/.env

  ln -s /app/var/.env /app/
else
  echo "external vars don't exist"
  rm /app/.env
  touch /app/var/.env

  ## manually generate a key because key generate --force fails
  echo -e "Generating key"
  APP_KEY=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
  echo -e "Generated app key: $APP_KEY"
  echo -e "APP_KEY=$APP_KEY" > /app/var/.env

  ln -s /app/var/.env /app/
fi

## check for DB up before starting the panel
zshoecho "Checking database status."
until nc -z -v -w30 $DB_HOST 3306

do
  echo "Waiting for database connection..."
  # wait for 5 seconds before check again
  sleep 5
done

## make sure the db is set up
php artisan migrate --seed --force

echo -e "Done\n"

## start php-fpm in the background
echo -e "Starting php-fpm in the background. \n"
php-fpm7 -D
echo -e "php-fpm started \n"

## start webserver
echo -e "Starting nginx in the foreground. \n"
nginx -g 'pid /tmp/nginx.pid; daemon off;'