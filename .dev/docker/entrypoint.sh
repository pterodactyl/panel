#!/bin/ash
cd /app

tini -- php-fpm

until nc -z -v -w30 $DB_HOST 3306
do
  echo "Waiting for database connection..."
  # wait for 5 seconds before check again
  sleep 5
done

if [ "$(cat .env)" == "" ]; then
  cat /app/.env.example > /app/.env
fi

if [ "$(cat .env | grep APP_KEY)" == "APP_KEY=" ]; then
  echo "Generating New Key"
  php artisan key:generate --force
fi

php artisan migrate --seed --force
echo "Done"
nginx -g 'pid /tmp/nginx.pid; daemon off;'
