# ----------------------------------
# Pterodactyl Panel Dockerfile
# ----------------------------------

FROM alpine:3.6

LABEL maintainer="Pterodactyl Software, <support@pterodactyl.io>"

WORKDIR /app

RUN apk add --no-cache --update caddy dcron curl tini php7 php7-bcmath php7-common php7-dom php7-fpm php7-gd php7-mbstring php7-openssl php7-zip php7-pdo php7-phar php7-json php7-pdo_mysql php7-session php7-ctype php7-tokenizer php7-zlib && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . ./

RUN composer install --ansi --no-dev --no-interaction && \
    cp -r .dev/docker/root/* / && \
    rm -rf .dev bootstrap/cache/* storage/framework/{cache,sessions,views}/* && \
    chown -R caddy:caddy . && \
    chmod -R 777 storage/* bootstrap/cache

ENTRYPOINT ["/bin/ash", "/app/entrypoint.sh"]
EXPOSE 80

CMD ["/sbin/tini", "--", "/usr/sbin/caddy", "-conf", "/etc/caddy/caddy.conf"]
