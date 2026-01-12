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

# Let Laravel use environment variables from Render
RUN php artisan key:generate || true

# Clear all Laravel caches
RUN php artisan config:clear || true
RUN php artisan route:clear || true
RUN php artisan view:clear || true
RUN php artisan cache:clear || true

# Create startup script to force config regeneration at runtime
RUN echo '#!/bin/bash' > /usr/local/bin/start-app.sh && \
    echo 'cd /var/www/html' >> /usr/local/bin/start-app.sh && \
    echo 'echo "Starting application..."' >> /usr/local/bin/start-app.sh && \
    echo 'php artisan config:clear' >> /usr/local/bin/start-app.sh && \
    echo 'php artisan config:cache' >> /usr/local/bin/start-app.sh && \
    echo 'php artisan route:clear' >> /usr/local/bin/start-app.sh && \
    echo 'php artisan route:cache' >> /usr/local/bin/start-app.sh && \
    echo 'echo "Config regenerated, starting Apache..."' >> /usr/local/bin/start-app.sh && \
    echo 'apache2-foreground' >> /usr/local/bin/start-app.sh && \
    chmod +x /usr/local/bin/start-app.sh

EXPOSE 80
CMD ["/usr/local/bin/start-app.sh"]
