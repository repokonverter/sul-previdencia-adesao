# ----------------------------------------------------
# 1. BUILDER STAGE: Prepara a imagem base PHP-FPM
# ----------------------------------------------------
FROM composer:latest AS composer

# Instala as dependências do Composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ----------------------------------------------------
# 2. APPLICATION STAGE: Imagem final com PHP-FPM
# ----------------------------------------------------
FROM php:8.3-fpm-alpine AS app

# Instala extensões PHP necessárias para o CakePHP e PostgreSQL
RUN apk add --no-cache nginx \
    # 👇 ADICIONADO: Bibliotecas de desenvolvimento para PostgreSQL (libpq-dev no Debian/postgres-dev no Alpine)
    && apk add --no-cache --virtual .build-deps \
        postgresql-dev \
        build-base \
    \
    # 2. Compila e instala as extensões do PHP
    && docker-php-ext-install pdo pdo_pgsql \
    \
    # 3. Limpa as dependências de build (para reduzir o tamanho da imagem)
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Copia as dependências e o código da aplicação
WORKDIR /var/www/html
COPY --from=composer /app/vendor /var/www/html/vendor
COPY . /var/www/html

# Ajusta permissões do CakePHP
RUN chown -R www-data:www-data /var/www/html/tmp \
    && chown -R www-data:www-data /var/www/html/logs \
    && chmod -R 775 /var/www/html/tmp \
    && chmod -R 775 /var/www/html/logs

# Configuração básica do Nginx para usar PHP-FPM
COPY deploy/nginx.conf /etc/nginx/conf.d/default.conf

# Comando de inicialização: Inicia o Nginx e o PHP-FPM
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]

EXPOSE 80
