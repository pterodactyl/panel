# syntax=docker/dockerfile:1

FROM --platform=$BUILDPLATFORM node:22-alpine3.23 AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci --no-audit --no-fund

COPY . .
RUN npm run build:force

FROM php:8.4-fpm-alpine3.23 AS php-base
WORKDIR /app

RUN apk add --no-cache \
        ca-certificates certbot certbot-nginx curl dcron freetype icu-libs \
        libjpeg-turbo libpng libwebp libzip mysql-client nginx supervisor tzdata \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS freetype-dev icu-dev libjpeg-turbo-dev libpng-dev libwebp-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl opcache pcntl pdo_mysql zip \
    && apk del .build-deps \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

FROM php-base AS dependencies
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-plugins --no-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction --no-scripts --no-plugins \
    && composer check-platform-reqs --no-dev \
    && mkdir -p bootstrap/cache storage/app/private storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs \
    && APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')" \
        APP_ENV=production APP_DEBUG=false APP_ENVIRONMENT_ONLY=true \
        CACHE_DRIVER=array SESSION_DRIVER=array QUEUE_DRIVER=sync \
        PTERODACTYL_EXTENSIONS_ENABLED=false php artisan package:discover --ansi \
    && rm -f bootstrap/cache/*.php

FROM php-base AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    QUEUE_WORKER_ENABLED=true \
    SCHEDULER_ENABLED=true \
    LARAVEL_OPTIMIZE=true \
    PHP_FPM_MAX_CHILDREN=9 \
    PHP_FPM_MAX_REQUESTS=200 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=128

COPY --from=dependencies /app /app
COPY --from=dependencies /usr/local/bin/composer /usr/local/bin/composer
COPY --from=assets /app/public/assets /app/public/assets

RUN apk add --no-cache su-exec icu-data-full \
    && mkdir -p /app/var /app/extensions /app/public/assets/extensions /var/run/php /var/run/nginx /var/log/supervisord \
    && chown -R nginx:nginx /app/bootstrap/cache /app/storage /app/var /app/extensions /app/public/assets/extensions \
    && chmod -R u=rwX,g=rwX,o= /app/bootstrap/cache /app/storage /app/var /app/extensions /app/public/assets/extensions \
    && printf '%s\n' \
        '* * * * * /usr/local/bin/php /app/artisan schedule:run >> /app/storage/logs/scheduler.log 2>&1' > /var/spool/cron/crontabs/nginx \
    && printf '%s\n' '0 23 * * * certbot renew --nginx --quiet' > /var/spool/cron/crontabs/root \
    && chmod 600 /var/spool/cron/crontabs/root /var/spool/cron/crontabs/nginx \
    && sed -i 's/ssl_session_cache/#ssl_session_cache/g' /etc/nginx/nginx.conf

COPY .github/docker/default.conf /etc/nginx/http.d/default.conf
COPY .github/docker/php.ini /usr/local/etc/php/conf.d/zz-panel.ini
COPY .github/docker/www.conf /usr/local/etc/php-fpm.conf
COPY .github/docker/supervisord.conf /etc/supervisord.conf

STOPSIGNAL SIGTERM
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD ["/bin/ash", "/app/.github/docker/healthcheck.sh"]

EXPOSE 80 443
ENTRYPOINT ["/bin/ash", "/app/.github/docker/entrypoint.sh"]
CMD ["supervisord", "-n", "-c", "/etc/supervisord.conf"]
