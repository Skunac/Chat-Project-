# Stage 1: Build dependencies
FROM composer:2.6 as composer_stage

WORKDIR /app

# Copy only the files needed for composer install
COPY backend/composer.json backend/composer.lock ./

# Install dependencies without dev dependencies
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader

# Stage 2: Build the application
FROM php:8.2-fpm-alpine as app_build

WORKDIR /app

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    git \
    unzip \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql

# Install Redis extension
RUN apk add --no-cache $PHPIZE_DEPS redis \
    && pecl install redis \
    && docker-php-ext-enable redis

# Copy composer dependencies from composer stage
COPY --from=composer_stage /app/vendor ./vendor
COPY backend .

# Run Symfony's cache clear and warmup
RUN php bin/console cache:clear --env=prod --no-debug
RUN php bin/console cache:warmup --env=prod --no-debug

# Stage 3: Create the final image
FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Install production dependencies only
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install Redis extension for production
RUN apk add --no-cache $PHPIZE_DEPS redis \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Copy application from build stage
COPY --from=app_build /app .

# Set proper permissions
RUN chown -R www-data:www-data var

# Create non-root user
RUN adduser --disabled-password --gecos "" app_user
USER app_user

# Configure PHP for production
COPY docker/prod/php/php.ini $PHP_INI_DIR/php.ini

CMD ["php-fpm"]