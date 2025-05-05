# Stage 1: Build dependencies
FROM composer:2.6 as composer_stage

WORKDIR /app

# Copy only the files needed for composer install
COPY backend/composer.json backend/composer.lock ./

# Install PHP Redis extension in the composer image
RUN apk add --no-cache $PHPIZE_DEPS redis \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install dependencies without dev dependencies
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader --ignore-platform-reqs

# Stage 2: Final image
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Install production dependencies and extensions
RUN apk add --no-cache postgresql-dev icu-dev \
    && docker-php-ext-install pdo pdo_pgsql intl

# Install Redis extension
RUN apk add --no-cache $PHPIZE_DEPS redis \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Copy custom PHP configuration
COPY docker/prod/php/php.ini $PHP_INI_DIR/php.ini

# Create log directories
RUN mkdir -p /var/log/php && \
    chown -R www-data:www-data /var/log/php

# Copy composer dependencies from composer stage
COPY --from=composer_stage /app/vendor ./vendor

# Copy application code
COPY backend .

# Set proper permissions
RUN mkdir -p var && chown -R www-data:www-data var

CMD ["php-fpm"]