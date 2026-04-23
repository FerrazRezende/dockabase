FROM dunglas/frankenphp:latest-php8.4

# Install PHP extensions using FrankenPHP's built-in helper
RUN install-php-extensions \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    sockets \
    redis

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    postgresql-client \
    unzip \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . /app

RUN chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage \
    && chmod -R 775 /app/bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000"]
