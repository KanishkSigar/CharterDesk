FROM php:8.4-apache-bookworm
RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev unzip \
    && docker-php-ext-install mbstring mysqli pdo_mysql \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN --mount=type=secret,id=composer_ca \
    if [ -f /run/secrets/composer_ca ]; then export COMPOSER_CAFILE=/run/secrets/composer_ca; fi; \
    composer install --no-dev --prefer-dist --no-interaction
COPY --chmod=0644 *.php *.html *.js *.css questions.json logo.png ./
COPY assets/ assets/
RUN chmod -R a+rX assets
