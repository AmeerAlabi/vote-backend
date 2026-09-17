# syntax=docker/dockerfile:1
#
# School Voting App API — production image.
# FrankenPHP bundles PHP and a Caddy web server, so one process serves the app.

FROM composer:2 AS vendor
WORKDIR /app
COPY laravel/composer.json laravel/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

FROM dunglas/frankenphp:1-php8.4
WORKDIR /app

RUN install-php-extensions pdo_pgsql pdo_mysql gd intl zip bcmath opcache

COPY laravel/ ./
COPY --from=vendor /app/vendor ./vendor
COPY --from=vendor /usr/bin/composer /usr/local/bin/composer
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && chmod +x /usr/local/bin/entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080 \
    SERVER_NAME=:8080

EXPOSE 8080
ENTRYPOINT ["entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
