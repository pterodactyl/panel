FROM alpine:3.8

WORKDIR /app

RUN apk add --no-cache --update ca-certificates certbot nginx dcron curl tini php7 php7-bcmath php7-common php7-dom php7-fpm php7-gd php7-mbstring php7-openssl php7-zip php7-pdo php7-phar php7-json php7-pdo_mysql php7-session php7-ctype php7-tokenizer php7-zlib php7-simplexml php7-fileinfo supervisor \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

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