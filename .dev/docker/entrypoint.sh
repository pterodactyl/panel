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
echo "Checking database status."
until nc -z -v -w30 $DB_HOST 3306

do
  echo "Waiting for database connection..."
  # wait for 5 seconds before check again
  sleep 5
done

## make sure the db is set up
echo -e "Migrating and Seeding DB"
php artisan migrate --force
php artisan db:seed --force

## start cronjobs for the queue
echo -e "Starting cron jobs"
crond

echo -e "Starting supervisord"
exec "$@"