# ----------------------------------------------------
# 1. BUILDER STAGE: Prepara a imagem base PHP-FPM
# ----------------------------------------------------
# MUDANÇA CRÍTICA: Usa PHP como base, não Composer.
# O Composer será instalado DENTRO desta imagem base.
FROM php:8.3-fpm-alpine AS builder

# Instala ferramentas necessárias (git, build-base para compilação)
RUN apk add --no-cache git build-base \
    # Instala as dependências de desenvolvimento para a extensão INTL
    && apk add --no-cache icu-dev \
    # Instala a extensão INTL
    && docker-php-ext-install intl \
    # Instala o Composer globalmente na imagem de build
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    # Limpa dependências de build desnecessárias (exceto git, etc.)
    && rm -rf /var/cache/apk/*

# Instala as dependências do Composer
WORKDIR /app
COPY composer.json composer.lock ./
# O Composer é executado em um ambiente que AGORA tem a extensão intl.
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ----------------------------------------------------
# 2. APPLICATION STAGE: Imagem final com PHP-FPM
# ----------------------------------------------------
FROM php:8.3-fpm-alpine AS app

# Instala Nginx e as extensões PHP (intl já está instalada no estágio final da imagem base)
RUN apk add --no-cache nginx \
    # 1. Instala as dependências de compilação para o PostgreSQL
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
# ⚠️ COPIAMOS A PASTA VENDOR DO ESTÁGIO 'builder'
COPY --from=builder /app/vendor /var/www/html/vendor
# ⚠️ COPIAMOS O ARQUIVO .ini DA EXTENSÃO INTL DO ESTÁGIO 'builder'
COPY --from=builder /usr/local/etc/php/conf.d/docker-php-ext-intl.ini /usr/local/etc/php/conf.d/
# COPIAMOS O CÓDIGO FONTE (que agora é pequeno devido ao .dockerignore)
COPY . /var/www/html

RUN mkdir -p /var/www/html/tmp \
    && mkdir -p /var/www/html/logs

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
