FROM alpine:3.8

WORKDIR /app

RUN apk add --no-cache --update ca-certificates certbot nginx dcron curl tini php7 php7-bcmath php7-common php7-dom php7-fpm php7-gd php7-mbstring php7-openssl php7-zip php7-pdo php7-phar php7-json php7-pdo_mysql php7-session php7-ctype php7-tokenizer php7-zlib php7-simplexml \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . ./

RUN cp .dev/docker/default.conf /etc/nginx/conf.d/default.conf \
 && cp .dev/docker/www.conf /etc/php7/php-fpm.d/www.conf \
 && cp .env.example .env \
 && composer install --no-dev

EXPOSE 80 443

RUN chown -R nginx:nginx . && chmod -R 777 storage/* bootstrap/cache

ENTRYPOINT ["ash", ".dev/app/entrypoint.sh"]