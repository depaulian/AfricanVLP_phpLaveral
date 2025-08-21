# Use PHP 8.0 FPM image based on Debian bullseye as the base image
FROM php:8.2-fpm-bullseye

# Install system dependencies
RUN apt-get update && apt-get install -y \
    supervisor \
    nginx \
    libncurses5-dev \
    zip \
    libzip-dev \
    libpng-dev \
    wkhtmltopdf \
    xvfb \
    mysql-common \
    htop \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libxpm-dev \
    libfreetype6 \
    default-mysql-client \
    vim \
    nano \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Configure and install PHP extensions
RUN docker-php-ext-configure zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd bcmath calendar zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
RUN curl -s https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin/ --filename=composer

# Set up wkhtmltopdf
RUN ln -s /usr/bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf \
    && chmod +x /usr/local/bin/wkhtmltopdf

# Set the working directory
WORKDIR /var/www/html/public

# Copy the application files and configuration files to the container
COPY . .
COPY ./docker/supervisord.conf /etc/supervisord.conf
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/zzz-docker.php-fpm.conf /usr/local/etc/php-fpm.d/zzz-docker.conf

# Set up environment variable flag for development
ARG ENV_IS_DEV

# Modify Supervisor configuration for development environment
RUN if [ "$ENV_IS_DEV" = "true" ]; then \
        sed -i "s~queue:work~queue:listen~" /etc/supervisord.conf; \
    fi

# Set permissions for storage and log directories
RUN chmod -R 777 storage/ \
    && touch storage/logs/laravel.log \
    && chmod 777 storage/logs/laravel.log \
    && touch /var/run/supervisor.sock \
    && chmod 777 /var/run/supervisor.sock \
    && touch /var/log/mysql-general.log \
    && chmod 777 /var/log/mysql-general.log

# Configure PHP settings
RUN echo 'memory_limit = 768M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini \
    && echo 'max_execution_time = 600' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini

# Install Xdebug and dependencies based on the environment
RUN chmod 777 ./docker/install-xdebug.sh \
    && ENV_IS_DEV=$ENV_IS_DEV /bin/bash ./docker/install-xdebug.sh
RUN chmod 777 ./docker/install-dependencies.sh \
    && ENV_IS_DEV=$ENV_IS_DEV /bin/bash ./docker/install-dependencies.sh

# Start Supervisor to manage processes
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
