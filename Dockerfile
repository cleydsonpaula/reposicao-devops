# Imagem base oficial: PHP 8.3 já com o servidor web Apache
FROM php:8.3-apache

# Extensão que o PHP usa para conversar com o MySQL
RUN docker-php-ext-install pdo_mysql

# Copia o código da aplicação para a pasta que o Apache publica
COPY app/ /var/www/html/

# A aplicação atende na porta 80 do contêiner
EXPOSE 80
