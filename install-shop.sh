cd /var/www/pterodactyl &&
rm -rf panel &&
git clone --branch 1.0-shop https://github.com/Frostbyt3/panel.git --single-branch &&
cd panel &&
cp -r * /var/www/pterodactyl &&
cd /var/www/pterodactyl &&
rm -rf panel &&
export NODE_OPTIONS=--openssl-legacy-provider &&
php artisan down &&
composer require stripe/stripe-php &&
composer require paypal/rest-api-sdk-php:* &&
composer require laraveldaily/laravel-invoices:^3.0 &&
mkdir /var/www/pterodactyl/storage/app/invoices &&
chown -R www-data:www-data /var/www/pterodactyl/* &&
apt install php8.0-intl &&
yarn install &&
yarn add @stripe/stripe-js &&
yarn run build:production &&
yarn build:production &&
php artisan optimize &&
php artisan migrate &&
php artisan view:clear &&
php artisan up