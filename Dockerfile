# Apache + PHP 8.2 with Postgres extensions
FROM php:8.2-apache

# Install pg + pdo_pgsql
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pgsql pdo pdo_pgsql \
    && a2enmod rewrite && rm -rf /var/lib/apt/lists/*

# Copy app
WORKDIR /var/www/html
COPY . /var/www/html

# Optional: if you use pretty URLs with .htaccess
# Ensure AllowOverride is honored
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Render auto-detects ports; Apache listens on 80 which is fine.
