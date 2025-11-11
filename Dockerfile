FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    postgresql-dev \
    oniguruma-dev \
    linux-headers

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    intl \
    opcache \
    zip \
    mbstring

# Install Redis extension
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP for development
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Configure OPcache for development (disabled caching)
RUN echo "opcache.enable=1" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=1" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> $PHP_INI_DIR/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> $PHP_INI_DIR/conf.d/opcache.ini

# Configure PHP settings
RUN echo "memory_limit=512M" >> $PHP_INI_DIR/conf.d/custom.ini \
    && echo "upload_max_filesize=20M" >> $PHP_INI_DIR/conf.d/custom.ini \
    && echo "post_max_size=20M" >> $PHP_INI_DIR/conf.d/custom.ini \
    && echo "max_execution_time=300" >> $PHP_INI_DIR/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/symfony

# Copy application files
COPY . /var/www/symfony/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/symfony/var \
    && chmod -R 775 /var/www/symfony/var

# Expose port 9000 for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
