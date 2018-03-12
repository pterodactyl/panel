# ----------------------------------
# Pterodactyl Panel Dockerfile
# ----------------------------------

FROM alpine:3.7

LABEL maintainer="Pterodactyl Software, <support@pterodactyl.io>"

WORKDIR /app

RUN apk add --no-cache wget ca-certificates && \
    wget -O /etc/apk/keys/phpearth.rsa.pub https://repos.php.earth/alpine/phpearth.rsa.pub && \
    echo "https://repos.php.earth/alpine/v3.7" >> /etc/apk/repositories && \
    apk add --no-cache --update caddy dcron curl tini php7.2 php7.2-bcmath php7.2-common php7.2-dom php7.2-fpm php7.2-gd php7.2-mbstring php7.2-openssl php7.2-zip php7.2-pdo php7.2-phar php7.2-json php7.2-pdo_mysql php7.2-session php7.2-ctype php7.2-tokenizer php7.2-zlib php7.2-simplexml && \
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
