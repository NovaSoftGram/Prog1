FROM php:8.1-apache

# Habilitar extensiones mysqli y pdo_mysql
RUN docker-php-ext-install mysqli pdo_mysql

# Copiar código fuente al contenedor
COPY src/ /var/www/html/

# Dar permisos a la carpeta de uploads
RUN mkdir -p /var/www/html/uploads/recibos \
    && chown -R www-data:www-data /var/www/html/uploads

# Exponer puerto HTTP
EXPOSE 80
