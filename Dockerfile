FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysql \
    libapache2-mod-php8.1 \
    && apt-get clean

RUN a2enmod rewrite php8.1

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html \
    && rm -f /var/www/html/index.html

RUN echo '<Directory /var/www/html>\nAllowOverride All\nRequire all granted\n</Directory>' \
    > /etc/apache2/conf-available/override.conf && a2enconf override

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]
