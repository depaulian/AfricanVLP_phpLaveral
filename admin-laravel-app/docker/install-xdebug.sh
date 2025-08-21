#!/bin/bash

############################################################
# Install Xdebug
# Only runs if we're not in a live environment (i.e., development)
############################################################

# Check if the environment is development (ENV_IS_DEV set to "true")
if [[ "${ENV_IS_DEV}" != "true" ]]; then
    echo "NOT installing Xdebug - Live environment detected"
    exit 0
fi

echo "Installing Xdebug"

# Copy Xdebug configuration
cp ./docker/xdebug.ini /usr/local/etc/php/conf.d/docker-xdebug-conf.ini

# Install Xdebug and enable it
if pecl install xdebug; then
  docker-php-ext-enable xdebug
  echo "Xdebug installed successfully"
else
  echo "Error installing Xdebug"
  exit 1
fi

# Set up Xdebug log file with appropriate permissions
touch /usr/local/etc/php/xdebug.log && chmod 777 /usr/local/etc/php/xdebug.log
