# TacoCraft SAAS - PHP Dockerfile
# Optimizado para Laravel con MinIO y Redis

ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-fpm-alpine

# Argumentos de construcción
ARG COMPOSER_VERSION=2.6
ARG NODE_VERSION=18

# Metadatos
LABEL maintainer="TacoCraft <hello@tacocraft.dev>"
LABEL description="Laravel optimizado para SAAS MVP con MinIO"
LABEL version="1.0.0"

# Variables de entorno
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/composer
ENV COMPOSER_CACHE_DIR=/composer/cache
ENV PATH=$PATH:/composer/vendor/bin
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"
ENV PHP_OPCACHE_MAX_ACCELERATED_FILES="20000"
ENV PHP_OPCACHE_MEMORY_CONSUMPTION="192"
ENV PHP_OPCACHE_MAX_WASTED_PERCENTAGE="10"

# Instalar dependencias del sistema
RUN apk add --no-cache \
    # Herramientas básicas
    bash \
    curl \
    git \
    unzip \
    zip \
    nano \
    htop \
    # Dependencias para extensiones PHP
    autoconf \
    g++ \
    make \
    # Librerías para imágenes
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    # Librerías para bases de datos
    postgresql-dev \
    # Librerías para Redis
    # Librerías para ZIP
    libzip-dev \
    # Librerías para XML
    libxml2-dev \
    # Librerías para LDAP
    openldap-dev \
    # Librerías para intl
    icu-dev \
    # Supervisor para procesos
    supervisor \
    # Cron para tareas programadas
    dcron

# Configurar extensión GD
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp

# Instalar extensiones PHP
RUN docker-php-ext-install -j$(nproc) \
    bcmath \
    exif \
    gd \
    intl \
    opcache \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pcntl \
    zip \
    sockets

# Instalar Redis extension
RUN pecl install redis-5.3.7 \
    && docker-php-ext-enable redis

# Instalar ImageMagick (opcional, para procesamiento avanzado de imágenes)
RUN apk add --no-cache imagemagick-dev \
    && pecl install imagick-3.7.0 \
    && docker-php-ext-enable imagick

# Limpiar cache de pecl
RUN rm -rf /tmp/pear

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --version=${COMPOSER_VERSION} \
    --install-dir=/usr/local/bin \
    --filename=composer

# Instalar Node.js y npm (para assets)
RUN apk add --no-cache nodejs npm

# Crear usuario para Laravel
RUN addgroup -g 1000 -S laravel \
    && adduser -u 1000 -S laravel -G laravel

# Crear directorios necesarios
RUN mkdir -p /var/www/html \
    && mkdir -p /var/log/supervisor \
    && mkdir -p /etc/supervisor/conf.d \
    && mkdir -p /composer/cache \
    && chown -R laravel:laravel /var/www/html \
    && chown -R laravel:laravel /composer

# Configuración de Supervisor
COPY supervisor.conf /etc/supervisor/conf.d/supervisord.conf

# Configuración de Cron
COPY crontab /etc/cron.d/laravel-scheduler
RUN chmod 0644 /etc/cron.d/laravel-scheduler \
    && crontab -u laravel /etc/cron.d/laravel-scheduler

# Scripts de utilidad
COPY scripts/ /usr/local/bin/
RUN chmod +x /usr/local/bin/*

# Configuración PHP personalizada
COPY php.ini /usr/local/etc/php/conf.d/custom.ini
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Directorio de trabajo
WORKDIR /var/www/html

# Cambiar al usuario laravel
USER laravel

# Instalar herramientas globales de Composer
RUN composer global require laravel/installer \
    && composer global require laravel/envoy \
    && composer global require friendsofphp/php-cs-fixer

# Volver a root para el entrypoint
USER root

# Script de entrada
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

# Exponer puerto
EXPOSE 9000

# Punto de entrada
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]