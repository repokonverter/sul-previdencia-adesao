# ----------------------------------------------------
# 1. BUILDER STAGE: Prepara a imagem base PHP-FPM (APENAS para instalar o Composer)
# ----------------------------------------------------
FROM php:8.3-fpm-alpine AS builder

# Instala ferramentas necessárias (git, build-base para compilação)
# IMPORTANTE: Apenas instalamos 'icu-dev' aqui para satisfazer as verificações de plataforma do Composer.
RUN apk add --no-cache git build-base icu-dev \
    # Instala o Composer globalmente na imagem de build
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/cache/apk/*

# Instala as dependências do Composer
WORKDIR /app
COPY composer.json composer.lock ./
# Este comando agora funciona pois o 'icu-dev' satisfaz o 'ext-intl'
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ----------------------------------------------------
# 2. APPLICATION STAGE: Imagem final de runtime (Inclui Nginx e PHP-FPM)
# ----------------------------------------------------
FROM php:8.3-fpm-alpine AS app

# Instala o Nginx e TODAS as extensões necessárias para o runtime.
RUN apk add --no-cache nginx \
    # icu-libs é a dependência de runtime do intl (Já corrigido, mas mantido)
    && apk add --no-cache icu-libs \
    \
    # 1. Instala as dependências de compilação (necessárias para intl e pdo_pgsql)
    # Tivemos que adicionar o icu-dev aqui de novo para o 'docker-php-ext-install intl' funcionar
    && apk add --no-cache --virtual .build-deps \
    postgresql-dev \
    build-base \
    icu-dev \
    \
    # 2. Compila e instala as extensões do PHP no runtime final
    # 👇 INSTALAMOS TODAS AS EXTENSÕES AQUI (incluindo intl)
    && docker-php-ext-install pdo pdo_pgsql intl \
    \
    # 3. Limpa as dependências de build (para reduzir o tamanho da imagem)
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

# Copia as dependências e o código da aplicação
WORKDIR /var/www/html
# ⚠️ COPIAMOS A PASTA VENDOR DO ESTÁGIO 'builder'
COPY --from=builder /app/vendor /var/www/html/vendor
# A cópia do INI do intl foi removida, pois 'docker-php-ext-install' faz isso.
# COPIAMOS O CÓDIGO FONTE
COPY . /var/www/html

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
