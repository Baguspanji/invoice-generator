# syntax=docker/dockerfile:1
#
# IceSum (Laravel) — single-container production image:
# nginx + php-fpm + queue worker (via supervisord).
# Frontend (public/build) TIDAK di-build di sini — pakai hasil build
# yang di-commit (whitelist /public/build di .gitignore).

FROM php:8.3-fpm-alpine AS app

# nginx, supervisor, curl (healthcheck) + installer ekstensi PHP
COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN apk add --no-cache nginx supervisor curl \
    && install-php-extensions \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && rm -rf /var/cache/apk/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Kode aplikasi (vendor/node_modules/tests dikecualikan via .dockerignore,
# public/build IKUT karena sudah di-commit)
COPY . .

# PHP config production
COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/app.ini

# Nginx + supervisord
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
RUN rm -f /etc/nginx/http.d/default.conf.default 2>/dev/null; \
    mkdir -p /run/nginx /var/log/supervisor && \
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && composer clear-cache \
    && cp -a /var/www/html/storage /opt/storage-skeleton \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod +x /var/www/html/docker/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
