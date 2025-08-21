#!/bin/bash

#####################################################
# Install packages
#####################################################


echo "Installing Composer packages"

# Install predis/predis regardless of environment
composer require predis/predis

# Check if the script is running in a development environment
if [[ "${ENV_IS_DEV}" == "true" ]]; then
    # Development-specific actions
    composer install --prefer-source --no-interaction
    cp .env.example .env
    php artisan key:generate
    echo "Development environment setup completed"
else
    # Production-specific actions
    composer install --no-dev --optimize-autoloader --no-interaction
    cp .env.example .env  # Assuming .env is correctly configured for production
    php artisan key:generate
    echo "Production environment setup completed"
fi

#install node dependencies
 npm install
#Build Vite assets
 npm run build
