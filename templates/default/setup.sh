#!/bin/bash

# TacoCraft Setup Script
# Este script instala las features de Laravel después de que los contenedores estén corriendo

set -e

echo "🚀 TacoCraft Setup - Installing Laravel Features"
echo "============================================="

# Verificar que docker-compose esté corriendo
if ! docker-compose ps | grep -q "Up"; then
    echo "❌ Error: Los contenedores no están corriendo."
    echo "Por favor ejecuta: docker-compose up -d"
    exit 1
fi

# Verificar que Laravel esté instalado
if [ ! -f "src/artisan" ]; then
    echo "❌ Error: Laravel no está instalado en src/"
    exit 1
fi

# Leer configuración de features
if [ -f "tacocraft-features.php" ]; then
    echo "📋 Leyendo configuración de features..."
    
    # Instalar Breeze si auth está habilitado
    if grep -q "'auth' => true" tacocraft-features.php; then
        echo "🔐 Instalando Laravel Breeze..."
        docker-compose exec app composer require laravel/breeze --dev
        docker-compose exec app php artisan breeze:install blade
        docker-compose exec app npm install
        docker-compose exec app npm run build
    fi
    
    # Instalar API si está habilitado
    if grep -q "'api' => true" tacocraft-features.php; then
        echo "🔌 Instalando Laravel API..."
        docker-compose exec app php artisan install:api
    fi
    
    # Instalar Horizon si está habilitado
    if grep -q "'horizon' => true" tacocraft-features.php; then
        echo "📊 Instalando Laravel Horizon..."
        docker-compose exec app composer require laravel/horizon
        docker-compose exec app php artisan horizon:install
    fi
    
    # Instalar Telescope si está habilitado
    if grep -q "'telescope' => true" tacocraft-features.php; then
        echo "🔭 Instalando Laravel Telescope..."
        docker-compose exec app composer require laravel/telescope --dev
        docker-compose exec app php artisan telescope:install
    fi
fi

# Ejecutar migraciones
echo "🗄️ Ejecutando migraciones..."
docker-compose exec app php artisan migrate --force

# Limpiar cache
echo "🧹 Limpiando cache..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Generar key si no existe
echo "🔑 Generando application key..."
docker-compose exec app php artisan key:generate --force

echo "✅ Setup completado exitosamente!"
echo ""
echo "🌐 Tu aplicación está disponible en:"
echo "   - HTTP: http://localhost"
echo "   - HTTPS: https://localhost"
echo ""
echo "📧 MailHog (testing emails): http://localhost:8025"
echo "📊 MinIO Console: http://localhost:9001"
echo ""
echo "Para ver los logs: docker-compose logs -f"
echo "Para detener: docker-compose down"