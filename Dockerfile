FROM php:7.4-fpm-alpine

WORKDIR /app

RUN apk add --no-cache --update ca-certificates dcron curl git supervisor tar unzip nginx libpng-dev libxml2-dev libzip-dev certbot yarn; \
    docker-php-ext-install bcmath; \
    docker-php-ext-install gd; \
    docker-php-ext-install mbstring; \
    docker-php-ext-install pdo; \
    docker-php-ext-install pdo_mysql; \
    docker-php-ext-install tokenizer; \
    docker-php-ext-install xml; \
    docker-php-ext-configure zip --with-libzip=/usr/include; \
    docker-php-ext-install zip; \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . ./

RUN cp .env.example .env \
 && composer install --no-dev --optimize-autoloader \
 && rm .env \
 && chown -R nginx:nginx . && chmod -R 777 storage/* bootstrap/cache 

RUN cp docker/default.conf /etc/nginx/conf.d/default.conf \
 && cat docker/www.conf > /usr/local/etc/php-fpm.d/www.conf \
 && rm /usr/local/etc/php-fpm.d/www.conf.default \
 && cat docker/supervisord.conf > /etc/supervisord.conf \
 && echo "* * * * * /usr/local/bin/php /app/artisan schedule:run >> /dev/null 2>&1" >> /var/spool/cron/crontabs/root \
 && sed -i s/ssl_session_cache/#ssl_session_cache/g /etc/nginx/nginx.conf \
 && mkdir -p /var/run/php /var/run/nginx

EXPOSE 80 443

ENTRYPOINT ["/bin/ash", "docker/entrypoint.sh"]

CMD [ "supervisord", "-n", "-c", "/etc/supervisord.conf" ]
