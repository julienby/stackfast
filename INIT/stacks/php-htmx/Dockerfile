# Un conteneur pour toutes les apps php-htmx de l'instance.
# Convention : la racine de l'instance est /var/www dans le conteneur.
#   /var/www/html                   = apps/php-htmx  (monté, DocumentRoot)
#   /var/www/stacks/php-htmx/lib    = stacks/php-htmx/lib (monté)
#   /var/www/stacks/php-htmx/vendor = construit ici depuis composer.json

FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

FROM php:8.4-apache
RUN apt-get update && apt-get install -y --no-install-recommends sqlite3 libsqlite3-dev libldap2-dev \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
 && docker-php-ext-install ldap pdo pdo_sqlite \
 && a2enmod rewrite headers \
 && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
 && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY php.ini /usr/local/etc/php/
COPY --from=deps /app/vendor /var/www/stacks/php-htmx/vendor
COPY lib /var/www/stacks/php-htmx/lib
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
EXPOSE 80
