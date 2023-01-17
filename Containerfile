# Stage 0 - Caddy
FROM        --platform=$TARGETOS/$TARGETARCH docker.io/library/caddy:latest AS caddy

# Stage 1 - Builder
FROM        --platform=$TARGETOS/$TARGETARCH registry.access.redhat.com/ubi9/nodejs-16-minimal AS builder

RUN         npm install -g yarn

WORKDIR     /var/www/pterodactyl

COPY        --chown=1001:0 public ./public
COPY        --chown=1001:0 resources/scripts ./resources/scripts
COPY        --chown=1001:0 .eslintignore .eslintrc.js .prettierrc.json package.json tailwind.config.js tsconfig.json vite.config.ts yarn.lock .

RUN         /opt/app-root/src/.npm-global/bin/yarn install --frozen-lockfile \
                && /opt/app-root/src/.npm-global/bin/yarn build \
                && rm -rf resources/scripts .eslintignore .eslintrc.yml .yarnrc.yml package.json tailwind.config.js tsconfig.json vite.config.ts yarn.lock node_modules

COPY        --chown=1001:0 app ./app
COPY        --chown=1001:0 bootstrap ./bootstrap
COPY        --chown=1001:0 config ./config
COPY        --chown=1001:0 database ./database
COPY        --chown=1001:0 resources/lang ./resources/lang
COPY        --chown=1001:0 resources/views ./resources/views
COPY        --chown=1001:0 routes ./routes
COPY        --chown=1001:0 .env.example ./.env
COPY        --chown=1001:0 artisan CHANGELOG.md composer.json composer.lock LICENSE.md README.md SECURITY.md .

# Stage 2 - Final
FROM        --platform=$TARGETOS/$TARGETARCH registry.access.redhat.com/ubi9/ubi-minimal

RUN         microdnf update -y \
                && rpm --install https://dl.fedoraproject.org/pub/epel/epel-release-latest-9.noarch.rpm \
                && rpm --install https://rpms.remirepo.net/enterprise/remi-release-9.rpm \
                && microdnf update -y \
                && microdnf install -y ca-certificates shadow-utils tar tzdata unzip wget \
                && microdnf module -y reset php \
                && microdnf module -y enable php:remi-8.1 \
                && microdnf install -y cronie php-{bcmath,cli,common,fpm,gd,gmp,intl,json,mbstring,mysqlnd,opcache,pdo,pecl-redis5,pecl-zip,phpiredis,pgsql,process,sodium,xml,zstd} supervisor \
                && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
                && rm /etc/php-fpm.d/www.conf \
                && useradd --home-dir /var/lib/caddy --create-home caddy \
                && mkdir /etc/caddy \
                && wget -O /usr/local/bin/yacron https://github.com/gjcarneiro/yacron/releases/download/0.17.0/yacron-0.17.0-x86_64-unknown-linux-gnu \
                && chmod 755 /usr/local/bin/yacron \
                && microdnf remove -y tar wget \
                && microdnf clean all

COPY        --chown=caddy:caddy --from=builder /var/www/pterodactyl /var/www/pterodactyl

WORKDIR     /var/www/pterodactyl

RUN         mkdir -p /tmp/pterodactyl/cache /tmp/pterodactyl/framework/{cache,sessions,views} storage/framework \
                && rm -rf bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache \
                && ln -s /tmp/pterodactyl/cache /var/www/pterodactyl/bootstrap/cache \
                && ln -s /tmp/pterodactyl/framework/cache /var/www/pterodactyl/storage/framework/cache \
                && ln -s /tmp/pterodactyl/framework/sessions /var/www/pterodactyl/storage/framework/sessions \
                && ln -s /tmp/pterodactyl/framework/views /var/www/pterodactyl/storage/framework/views \
                && chmod -R 755 /var/www/pterodactyl/storage/* /tmp/pterodactyl/cache \
                && chown -R caddy:caddy /var/www/pterodactyl /tmp/pterodactyl/{cache,framework}

USER        caddy
ENV         USER=caddy

RUN         composer install --no-dev --optimize-autoloader \
                && rm -rf bootstrap/cache/*.php \
                && rm -rf .env storage/logs/*.log

COPY        --from=caddy /usr/bin/caddy /usr/local/bin/caddy
COPY        .github/docker/Caddyfile /etc/caddy/Caddyfile
COPY        .github/docker/php-fpm.conf /etc/php-fpm.conf
COPY        .github/docker/supervisord.conf /etc/supervisord.conf
COPY        .github/docker/yacron.yaml /etc/yacron.yaml

EXPOSE      8080
CMD         ["/usr/bin/supervisord", "--configuration=/etc/supervisord.conf"]
