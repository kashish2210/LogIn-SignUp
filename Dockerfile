# Apache + PHP 8.2 with Postgres extensions
FROM php:8.2-apache

# Install pgsql + pdo_pgsql
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pgsql pdo pdo_pgsql \
    && a2enmod rewrite && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy app
COPY . /var/www/html

# Enable .htaccess overrides (optional, for pretty URLs)
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Expose port 80
EXPOSE 80
