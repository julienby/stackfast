# Un conteneur pour toutes les apps php-htmx de l'instance.
# Convention : la racine de l'instance est /var/www dans le conteneur.
#   /var/www/html                   = apps/php-htmx  (monté, DocumentRoot)
#   /var/www/stacks/php-htmx/lib    = stacks/php-htmx/lib (monté)
#   /var/www/stacks/php-htmx/vendor = construit ici depuis composer.json

FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

FROM php:8.5-apache
RUN apt-get update && apt-get install -y --no-install-recommends sqlite3 \
 && rm -rf /var/lib/apt/lists/* \
 && a2enmod rewrite headers \
 && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
 && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

ADD https://github.com/yt-dlp/yt-dlp/releases/download/2026.08.19/yt-dlp_linux /usr/local/bin/yt-dlp
RUN echo '58162f9bfdc27458ea47bfcb311cf47028f17d8154a8bf7d689861d46399230a  /usr/local/bin/yt-dlp' | sha256sum -c - \
 && chmod 755 /usr/local/bin/yt-dlp

COPY php.ini /usr/local/etc/php/
COPY --from=deps /app/vendor /var/www/stacks/php-htmx/vendor
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
EXPOSE 80
