FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy all files to Apache folder
COPY . /var/www/html/

# Give permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Use port 10000 (Render requirement)
RUN sed -i 's/80/10000/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 10000