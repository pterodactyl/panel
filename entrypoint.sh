#!/bin/ash
tini -- php-fpm

until nc -z -v -w30 $DB_HOST 3306
do
  echo "Waiting for database connection..."
  # wait for 5 seconds before check again
  sleep 5
done

#sed -i s/DB_HOST=127.0.0.1/DB_HOST=$DB_HOST/g /app/.env
#sed -i s/DB_DATABASE=panel/DB_DATABASE=$DB_DATABASE/g /app/.env
#sed -i s/DB_USERNAME=pterodactyl/DB_USERNAME=$DB_USERNAME/g /app/.env
#sed -i s/DB_PASSWORD=/DB_PASSWORD=$DB_PASSWORD/g /app/.env

#echo "1"
#chown -R www-data:www-data .
#echo "2"
chmod -R 777 storage/* bootstrap/cache /var/run/php /app/.env
echo "3"

if [ "$(cat .env)" == "" ]; then
  cat /app/.env.example > /app/.env
fi

if [ "$(cat .env | grep APP_KEY)" == "APP_KEY=" ]; then
  echo "Generating New Key"
  php artisan key:generate --force
fi

echo "4"
php artisan migrate --force
echo "5"
php artisan db:seed --force
echo "Done"
nginx -g 'pid /tmp/nginx.pid; daemon off;'
