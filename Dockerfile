FROM php:8.2-apache

# Install MySQL PDO driver
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite and mod_headers
RUN a2enmod rewrite headers

# Allow .htaccess overrides in /var/www/html
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Copy all project files into web root
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 80
