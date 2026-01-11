# Use the official PHP 8.2 Apache image
FROM php:8.2-apache

# Enable required Apache modules
RUN a2enmod rewrite

# Install system dependencies and PHP extensions
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
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy package files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy application code
COPY . .

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Create .env if it doesn't exist
RUN cp .env.example .env 2>/dev/null || echo "APP_NAME=WebXemPhim\nAPP_ENV=production\nAPP_DEBUG=false\nAPP_KEY=\nDB_CONNECTION=pgsql\nLOG_CHANNEL=stack" > .env

# Generate app key
RUN php artisan key:generate

# Clear and cache config
RUN php artisan config:cache 2>/dev/null || true
RUN php artisan route:cache 2>/dev/null || true

# Expose port 80
EXPOSE 80

# Simple startup command
CMD ["apache2-foreground"]
