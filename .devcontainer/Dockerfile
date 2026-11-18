FROM mcr.microsoft.com/devcontainers/php:8.3-bookworm

# Install PHP 8.3
RUN apt update && apt install -y libjpeg-dev mariadb-client libpng-dev libxml2-dev libwebp-dev libzip-dev libsodium-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-configure zip \
    && docker-php-ext-install bcmath gd pdo_mysql zip sodium \
    && docker-php-ext-enable bcmath gd pdo_mysql zip sodium
