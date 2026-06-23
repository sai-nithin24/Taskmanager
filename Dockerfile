FROM php:8.2-apache

# Install the MySQL PDO driver required for your database connection
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy all project files into the Apache container web root
COPY . /var/www/html/

# Set correct file ownership permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80