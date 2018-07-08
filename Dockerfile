FROM alpine:3.8

WORKDIR /app

RUN apk add --no-cache wget ca-certificates && \
    wget -O /etc/apk/keys/phpearth.rsa.pub https://repos.php.earth/alpine/phpearth.rsa.pub && \
    echo "https://repos.php.earth/alpine/v3.7" >> /etc/apk/repositories && \
    apk add --no-cache --update certbot nginx dcron curl tini php7.2 php7.2-bcmath php7.2-common php7.2-dom php7.2-fpm php7.2-gd php7.2-mbstring php7.2-openssl php7.2-zip php7.2-pdo php7.2-phar php7.2-json php7.2-pdo_mysql php7.2-session php7.2-ctype php7.2-tokenizer php7.2-zlib php7.2-simplexml && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . ./

COPY .dev/default.conf /etc/nginx/conf.d/default.conf

RUN cp .env.example .env && composer install --no-dev

EXPOSE 80 443

RUN chown -R www-data:www-data . && chmod -R 777 storage/* bootstrap/cache /var/run/php

ENTRYPOINT ["ash", ".dev/entrypoint.sh"]

