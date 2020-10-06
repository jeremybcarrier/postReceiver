FROM php:7.3.8-apache
COPY html/ /var/www/html/
#COPY httpd/000-default-ssl.conf /etc/apache2/sites-enabled
COPY httpd/000-default.conf /etc/apache2/sites-enabled
#COPY httpd/server.key /etc/apache2/
#COPY httpd/server.crt /etc/apache2
COPY httpd/php.ini "$PHP_INI_DIR/php.ini"
#COPY httpd/config.user.inc.php /etc/phpmyadmin/config.user.inc.php
RUN a2enmod ssl
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli