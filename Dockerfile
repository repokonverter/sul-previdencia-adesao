# ----------------------------------------------------
# 1. BUILDER STAGE: Prepara a imagem base PHP-FPM (APENAS para instalar o Composer)
# ----------------------------------------------------
FROM php:8.3-fpm-alpine AS builder

# Instala ferramentas necessárias (git, build-base para compilação)
# IMPORTANTE: Instalamos 'icu-dev' e a extensão 'intl' para satisfazer o Composer.
RUN apk add --no-cache git build-base icu-dev nodejs npm \
    && docker-php-ext-install intl \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/cache/apk/*

# Instala as dependências do Composer
WORKDIR /app

ENV SECURITY_SALT="dc4ce4fc849f181765000f867543582de7564362bdfab5f85dc2f89aa534ab41"

# COPY composer.json composer.lock ./
COPY . /app

RUN cp config/app_local.example.php config/app_local.php

RUN composer install --optimize-autoloader --no-interaction

# ----------------------------------------------------
# 2. APPLICATION STAGE: Imagem final de runtime (Inclui Nginx e PHP-FPM)
# ----------------------------------------------------
FROM php:8.3-fpm-alpine AS app

# Instala o Nginx, icu-libs e a biblioteca de runtime do PostgreSQL (libpq)
RUN apk add --no-cache nginx \
    && apk add --no-cache icu-libs \
    # 👇 ADICIONADO: libpq para o runtime do pdo_pgsql
    && apk add --no-cache libpq \
    # 👇 GD runtime dependencies
    && apk add --no-cache libpng libjpeg-turbo freetype \
    \
    # 1. Instala as dependências de compilação (necessárias para intl e pdo_pgsql)
    && apk add --no-cache --virtual .build-deps \
    postgresql-dev \
    build-base \
    icu-dev \
    # 👇 GD build dependencies
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    \
    # 2. Compila e instala as extensões do PHP no runtime final
    # 👇 Configure GD
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql intl gd \
    \
    # 3. Limpa as dependências de build (para reduzir o tamanho da imagem)
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Copia as dependências e o código da aplicação
WORKDIR /var/www/html

COPY --from=builder /app/vendor /var/www/html/vendor
COPY --from=builder /app/webroot /var/www/html/webroot
COPY --from=builder /app/config /var/www/html/config

# COPIAMOS O RESTANTE DO CÓDIGO FONTE (src, templates, config)
# Copiamos apenas as pastas necessárias, excluindo o webroot local problemático.
COPY bin /var/www/html/bin
COPY src /var/www/html/src
COPY templates /var/www/html/templates

# Cria e ajusta permissões para as pastas logs e tmp do CakePHP
RUN mkdir -p /var/www/html/tmp \
    && mkdir -p /var/www/html/logs

# Ajusta permissões do CakePHP
RUN chown -R www-data:www-data /var/www/html/tmp \
    && chown -R www-data:www-data /var/www/html/logs \
    && chmod -R 775 /var/www/html/tmp \
    && chmod -R 775 /var/www/html/logs

# Configuração Nginx (Substituição do arquivo mestre)
COPY deploy/nginx.conf /etc/nginx/conf.d/app.conf
COPY deploy/nginx-master.conf /etc/nginx/nginx.conf

# Comando de inicialização: Inicia o Nginx e o PHP-FPM
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]

EXPOSE 80
