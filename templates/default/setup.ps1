# TacoCraft Setup Script for Windows
# Este script instala las features de Laravel después de que los contenedores estén corriendo

Write-Host "🚀 TacoCraft Setup - Installing Laravel Features" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green

# Verificar que docker-compose esté corriendo
$containers = docker-compose ps
if (-not ($containers -match "Up")) {
    Write-Host "❌ Error: Los contenedores no están corriendo." -ForegroundColor Red
    Write-Host "Por favor ejecuta: docker-compose up -d" -ForegroundColor Yellow
    exit 1
}

# Verificar que Laravel esté instalado
if (-not (Test-Path "src\artisan")) {
    Write-Host "❌ Error: Laravel no está instalado en src\" -ForegroundColor Red
    exit 1
}

# Leer configuración de features
if (Test-Path "tacocraft-features.php") {
    Write-Host "📋 Leyendo configuración de features..." -ForegroundColor Cyan
    
    $featuresContent = Get-Content "tacocraft-features.php" -Raw
    
    # Instalar Breeze si auth está habilitado
    if ($featuresContent -match "'auth' => true") {
        Write-Host "🔐 Instalando Laravel Breeze..." -ForegroundColor Cyan
        docker-compose exec app composer require laravel/breeze --dev
        docker-compose exec app php artisan breeze:install blade
        docker-compose exec app npm install
        docker-compose exec app npm run build
    }
    
    # Instalar API si está habilitado
    if ($featuresContent -match "'api' => true") {
        Write-Host "🔌 Instalando Laravel API..." -ForegroundColor Cyan
        docker-compose exec app php artisan install:api
    }
    
    # Instalar Horizon si está habilitado
    if ($featuresContent -match "'horizon' => true") {
        Write-Host "📊 Instalando Laravel Horizon..." -ForegroundColor Cyan
        docker-compose exec app composer require laravel/horizon
        docker-compose exec app php artisan horizon:install
    }
    
    # Instalar Telescope si está habilitado
    if ($featuresContent -match "'telescope' => true") {
        Write-Host "🔭 Instalando Laravel Telescope..." -ForegroundColor Cyan
        docker-compose exec app composer require laravel/telescope --dev
        docker-compose exec app php artisan telescope:install
    }
}

# Ejecutar migraciones
Write-Host "🗄️ Ejecutando migraciones..." -ForegroundColor Cyan
docker-compose exec app php artisan migrate --force

# Limpiar cache
Write-Host "🧹 Limpiando cache..." -ForegroundColor Cyan
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Generar key si no existe
Write-Host "🔑 Generando application key..." -ForegroundColor Cyan
docker-compose exec app php artisan key:generate --force

Write-Host "✅ Setup completado exitosamente!" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 Tu aplicación está disponible en:" -ForegroundColor Yellow
Write-Host "   - HTTP: http://localhost" -ForegroundColor White
Write-Host "   - HTTPS: https://localhost" -ForegroundColor White
Write-Host ""
Write-Host "📧 MailHog (testing emails): http://localhost:8025" -ForegroundColor White
Write-Host "📊 MinIO Console: http://localhost:9001" -ForegroundColor White
Write-Host ""
Write-Host "Para ver los logs: docker-compose logs -f" -ForegroundColor Gray
Write-Host "Para detener: docker-compose down" -ForegroundColor Gray