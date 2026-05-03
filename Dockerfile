FROM php:8.2-apache

# Install MariaDB and PHP extensions
RUN apt-get update && apt-get install -y mariadb-server \
    && docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Copy config.sample.php to config.php so it works out of the box
# Note: In a real app, you would inject environment variables. For the hackathon demo, we use the sample.
RUN cp /var/www/html/config.sample.php /var/www/html/config.php

# Update config.php to use no password for local root MySQL
RUN sed -i "s/define('DB_PASS', '');/define('DB_PASS', '');/" /var/www/html/config.php

# Create entrypoint script
COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Change Apache port to 8080 (Cloud Run default)
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
