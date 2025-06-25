FROM php:8.3-apache

# Installer les bibliothèques nécessaires pour pdo_sqlite
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    pkg-config \
    && docker-php-ext-install pdo pdo_sqlite


RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd


# Copier le code source
COPY . /var/www/html/

# Donner les droits à Apache
RUN chown -R www-data:www-data /var/www/html

# Activer mod_rewrite (utile si tu fais du .htaccess plus tard)
RUN a2enmod rewrite

# Exposer le port 80
EXPOSE 80
