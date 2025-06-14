cd /var/www/pterodactyl &&
rm -rf panel &&
git clone --branch 1.0-shop https://github.com/Frostbyt3/panel.git --single-branch &&
cd panel &&
cp -r * /var/www/pterodactyl &&
cd /var/www/pterodactyl &&
rm -rf panel &&
export NODE_OPTIONS=--openssl-legacy-provider &&
php artisan down &&
yarn build:production &&
php artisan view:clear &&
php artisan up