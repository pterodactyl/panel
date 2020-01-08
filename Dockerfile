FROM php:7.2-fpm-alpine

WORKDIR /app

RUN apk add --no-cache --update ca-certificates dcron curl git supervisor tar unzip; \
    docker-php-ext-install bcmath; \
    apk add --no-cache libpng-dev; \
    docker-php-ext-install gd; \
    docker-php-ext-install mbstring; \
    docker-php-ext-install pdo; \
    docker-php-ext-install pdo_mysql; \
    docker-php-ext-install tokenizer; \
    apk add --no-cache libxml2-dev; \
    docker-php-ext-install xml; \
    docker-php-ext-install zip; \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . ./

RUN cp .env.example .env \
 && composer install --no-dev --optimize-autoloader \
 && rm .env \
 && chown -R nginx:nginx . && chmod -R 777 storage/* bootstrap/cache 

RUN cp .dev/docker/default.conf /etc/nginx/conf.d/default.conf \
 && cp .dev/docker/www.conf /etc/php7/php-fpm.d/www.conf \
 && cat .dev/docker/supervisord.conf > /etc/supervisord.conf \
 && echo "* * * * * /usr/bin/php /app/artisan schedule:run >> /dev/null 2>&1" >> /var/spool/cron/crontabs/root \
 && sed -i s/ssl_session_cache/#ssl_session_cache/g /etc/nginx/nginx.conf \
 && mkdir -p /var/run/php /var/run/nginx

EXPOSE 80 443

ENTRYPOINT ["/bin/ash", ".dev/docker/entrypoint.sh"]

CMD [ "supervisord", "-n", "-c", "/etc/supervisord.conf" ]
