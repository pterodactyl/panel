# Stage 0: Build frontend assets
FROM --platform=$TARGETOS/$TARGETARCH mhart/alpine-node:14 AS builder
WORKDIR /app
COPY . ./
RUN yarn install --frozen-lockfile \
    && yarn run build:production

# Stage 1: Build PHP application container
FROM --platform=$TARGETOS/$TARGETARCH php:8.2-fpm-alpine
WORKDIR /app

# Copy source + built assets
COPY . ./
COPY --from=builder /app/public/assets ./public/assets

# Install system deps, PHP extensions, and Redis extension
RUN apk add --no-cache --update \
      ca-certificates \
      dcron \
      curl \
      git \
      supervisor \
      tar \
      unzip \
      nginx \
      libpng-dev \
      libxml2-dev \
      libzip-dev \
      certbot \
      certbot-nginx \
      pcre-dev \
      $PHPIZE_DEPS \
  && docker-php-ext-configure zip \
  && docker-php-ext-install \
      bcmath \
      gd \
      pdo_mysql \
      zip \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && apk del pcre-dev $PHPIZE_DEPS \
  && rm -rf /tmp/pear

# Composer & application setup
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
  && cp .env.example .env \
  && mkdir -p bootstrap/cache storage/logs storage/framework/{sessions,views,cache} \
  && chmod -R 777 bootstrap storage \
  && composer install --no-dev --optimize-autoloader \
  && rm -rf .env bootstrap/cache/*.php \
  && mkdir -p storage/logs \
  && chown -R nginx:nginx .

# Configure cron, certbot, and PHP-FPM/nginx runtime
RUN rm /usr/local/etc/php-fpm.conf \
  && echo "* * * * * /usr/local/bin/php /app/artisan schedule:run >> /dev/null 2>&1" >> /var/spool/cron/crontabs/root \
  && echo "0 23 * * * certbot renew --nginx --quiet" >> /var/spool/cron/crontabs/root \
  && sed -i 's/ssl_session_cache/#ssl_session_cache/' /etc/nginx/nginx.conf \
  && mkdir -p /var/run/php /var/run/nginx

# Nginx, PHP-FPM & Supervisor configs
COPY .github/docker/default.conf /etc/nginx/http.d/default.conf
COPY .github/docker/www.conf     /usr/local/etc/php-fpm.conf
COPY .github/docker/supervisord.conf /etc/supervisord.conf

# Expose HTTP/S and launch entrypoint
EXPOSE 80 443
ENTRYPOINT ["/bin/ash", ".github/docker/entrypoint.sh"]
CMD ["supervisord", "-n", "-c", "/etc/supervisord.conf"]
