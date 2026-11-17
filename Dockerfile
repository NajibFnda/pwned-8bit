FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    libicu-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install intl mysqli opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

COPY . /var/www/html/

RUN mkdir -p writable/cache writable/logs writable/session writable/uploads writable/debugbar \
    && chown -R www-data:www-data /var/www/html/writable \
    && chmod -R 775 /var/www/html/writable \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && printf '<Directory /var/www/html/public>\n    Options Indexes FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/ci4-public.conf \
    && a2enconf ci4-public

CMD ["sh", "-c", "PORT=${PORT:-8080}; sed -ri \"s/Listen [0-9]+/Listen ${PORT}/\" /etc/apache2/ports.conf; sed -ri \"s/<VirtualHost \\*:[0-9]+>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-available/000-default.conf; a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true; a2enmod mpm_prefork >/dev/null 2>&1 || true; chown -R www-data:www-data /var/www/html/writable; php /var/www/html/docker-init-db.php; exec apache2-foreground"]
