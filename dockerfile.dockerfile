# Gunakan image PHP + Apache
FROM php:8.2-apache

# Copy file PHP ke direktori Apache
COPY . /var/www/html/

# Atur permission (opsional)
RUN chown -R www-data:www-data /var/www/html

# Port yang digunakan Apache
EXPOSE 80