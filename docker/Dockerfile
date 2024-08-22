# Start with PHP 8.2 and Apache image
FROM php:8.2-apache

# Update system and install necessary libraries
RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    git \
    unzip \
    curl \
    pdftk \
    libzip-dev \
    zlib1g-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring xml curl zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install XDebug via PECL and enable it
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Copy application files
COPY ./src /var/www/html

# Start Apache server in the foreground
CMD ["apache2-foreground"]
