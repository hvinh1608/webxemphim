FROM php:8.2-apache

RUN a2enmod rewrite

# Apache: trỏ về public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# System deps
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# ENV - Force use production config
RUN rm -f .env && \
    echo "APP_NAME=WebXemPhim" > .env && \
    echo "APP_ENV=production" >> .env && \
    echo "APP_DEBUG=false" >> .env && \
    echo "APP_KEY=base64:SG3hJzVxK2mPvFc8dGbNqRwY5tL7pM9eA1iOuHkT6s=" >> .env && \
    echo "APP_URL=https://webxemphim.onrender.com" >> .env && \
    echo "DB_CONNECTION=pgsql" >> .env
RUN php artisan key:generate

# Clear all Laravel caches and force reload config
RUN php artisan config:clear || true
RUN php artisan route:clear || true
RUN php artisan view:clear || true
RUN php artisan cache:clear || true
RUN php artisan config:cache || true

EXPOSE 80
CMD ["apache2-foreground"]
