#!/bin/bash

# Start MariaDB service
service mariadb start

# Wait for MariaDB to start
sleep 3

# Setup the database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS voterflow;"
mysql -u root voterflow < /var/www/html/db_setup.sql

# Start Apache in the foreground
apache2-foreground
