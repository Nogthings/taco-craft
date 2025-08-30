#!/bin/bash
# TacoCraft SAAS - PHP Container Entrypoint
# Configuración automática para Laravel

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para logging
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO: $1${NC}"
}

# Función para esperar servicios
wait_for_service() {
    local host=$1
    local port=$2
    local service_name=$3
    local max_attempts=30
    local attempt=1

    info "Esperando a que $service_name esté disponible en $host:$port..."
    
    while ! nc -z "$host" "$port" >/dev/null 2>&1; do
        if [ $attempt -eq $max_attempts ]; then
            error "$service_name no está disponible después de $max_attempts intentos"
            exit 1
        fi
        
        info "Intento $attempt/$max_attempts - Esperando $service_name..."
        sleep 2
        ((attempt++))
    done
    
    log "$service_name está disponible!"
}

# Función para configurar permisos
setup_permissions() {
    log "Configurando permisos..."
    
    # Crear directorios necesarios
    mkdir -p /var/www/html/storage/logs
    mkdir -p /var/www/html/storage/framework/cache
    mkdir -p /var/www/html/storage/framework/sessions
    mkdir -p /var/www/html/storage/framework/views
    mkdir -p /var/www/html/storage/app/public
    mkdir -p /var/www/html/bootstrap/cache
    
    # Configurar permisos
    chown -R laravel:laravel /var/www/html/storage
    chown -R laravel:laravel /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/storage
    chmod -R 775 /var/www/html/bootstrap/cache
    
    log "Permisos configurados correctamente"
}

# Función para instalar dependencias
install_dependencies() {
    if [ -f "/var/www/html/composer.json" ]; then
        log "Instalando dependencias de Composer..."
        cd /var/www/html
        
        # Cambiar a usuario laravel para composer
        su laravel -c "composer install --no-dev --optimize-autoloader --no-interaction"
        
        log "Dependencias de Composer instaladas"
    else
        warn "No se encontró composer.json, omitiendo instalación de dependencias"
    fi
}

# Función para configurar Laravel
setup_laravel() {
    if [ -f "/var/www/html/artisan" ]; then
        log "Configurando Laravel..."
        cd /var/www/html
        
        # Generar clave de aplicación si no existe
        if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:{{APP_KEY}}" ]; then
            log "Generando clave de aplicación..."
            su laravel -c "php artisan key:generate --force"
        fi
        
        # Crear enlace simbólico para storage
        if [ ! -L "/var/www/html/public/storage" ]; then
            log "Creando enlace simbólico para storage..."
            su laravel -c "php artisan storage:link"
        fi
        
        # Limpiar y cachear configuración
        log "Optimizando Laravel..."
        su laravel -c "php artisan config:clear"
        su laravel -c "php artisan route:clear"
        su laravel -c "php artisan view:clear"
        
        # En producción, cachear configuración
        if [ "$APP_ENV" = "production" ]; then
            su laravel -c "php artisan config:cache"
            su laravel -c "php artisan route:cache"
            su laravel -c "php artisan view:cache"
        fi
        
        log "Laravel configurado correctamente"
    else
        warn "No se encontró artisan, omitiendo configuración de Laravel"
    fi
}

# Función para ejecutar migraciones
run_migrations() {
    if [ "$RUN_MIGRATIONS" = "true" ] && [ -f "/var/www/html/artisan" ]; then
        log "Ejecutando migraciones..."
        cd /var/www/html
        
        # Esperar a que la base de datos esté disponible
        wait_for_service "$DB_HOST" "$DB_PORT" "MySQL"
        
        # Ejecutar migraciones
        su laravel -c "php artisan migrate --force"
        
        log "Migraciones ejecutadas"
    fi
}

# Función para configurar MinIO
setup_minio() {
    if [ "$SETUP_MINIO" = "true" ]; then
        log "Configurando MinIO..."
        
        # Esperar a que MinIO esté disponible
        wait_for_service "minio" "9000" "MinIO"
        
        # Instalar mc (MinIO Client) si no está disponible
        if ! command -v mc &> /dev/null; then
            info "Instalando MinIO Client..."
            curl -o /usr/local/bin/mc https://dl.min.io/client/mc/release/linux-amd64/mc
            chmod +x /usr/local/bin/mc
        fi
        
        # Configurar alias de MinIO
        mc alias set minio http://minio:9000 "$MINIO_KEY" "$MINIO_SECRET"
        
        # Crear bucket si no existe
        if ! mc ls minio/"$MINIO_BUCKET" &> /dev/null; then
            info "Creando bucket $MINIO_BUCKET..."
            mc mb minio/"$MINIO_BUCKET"
            
            # Configurar política pública para imágenes
            mc anonymous set download minio/"$MINIO_BUCKET"/public
        fi
        
        log "MinIO configurado correctamente"
    fi
}

# Función para configurar cron
setup_cron() {
    if [ "$ENABLE_CRON" = "true" ]; then
        log "Configurando cron para Laravel Scheduler..."
        
        # Iniciar cron
        crond -l 2 -f &
        
        log "Cron configurado y ejecutándose"
    fi
}

# Función principal
main() {
    log "Iniciando TacoCraft SAAS Container..."
    
    # Mostrar información del entorno
    info "PHP Version: $(php -v | head -n1)"
    info "Environment: ${APP_ENV:-local}"
    info "Debug Mode: ${APP_DEBUG:-true}"
    
    # Esperar servicios dependientes
    if [ -n "$DB_HOST" ] && [ -n "$DB_PORT" ]; then
        wait_for_service "$DB_HOST" "$DB_PORT" "Database"
    fi
    
    if [ -n "$REDIS_HOST" ]; then
        wait_for_service "$REDIS_HOST" "${REDIS_PORT:-6379}" "Redis"
    fi
    
    # Configurar aplicación
    setup_permissions
    install_dependencies
    setup_laravel
    run_migrations
    setup_minio
    setup_cron
    
    log "Configuración completada. Iniciando PHP-FPM..."
    
    # Ejecutar comando pasado como argumento
    exec "$@"
}

# Manejo de señales
trap 'log "Recibida señal de terminación, cerrando..."' SIGTERM SIGINT

# Ejecutar función principal
main "$@"