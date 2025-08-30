<?php

namespace TacoCraft\Templates;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class TemplateManager
{
    private Filesystem $filesystem;

    private string $templatesPath;

    public function __construct()
    {
        $this->filesystem = new Filesystem;
        $this->templatesPath = dirname(__DIR__, 2).'/templates';
    }

    public function copyTemplate(string $stack, string $projectPath, array $config): void
    {
        $templatePath = $this->templatesPath.'/'.$stack;

        if (! $this->filesystem->exists($templatePath)) {
            throw new \RuntimeException(sprintf(
                'Template "%s" not found at: %s',
                $stack,
                $templatePath
            ));
        }

        // Copiar archivos del template
        $this->copyTemplateFiles($templatePath, $projectPath);

        // Aplicar configuraciones específicas del stack
        $this->applyStackConfiguration($stack, $projectPath, $config);
    }

    private function copyTemplateFiles(string $templatePath, string $projectPath): void
    {
        $finder = new Finder;
        $finder->files()->in($templatePath);

        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            $targetPath = $projectPath.'/'.$relativePath;

            // Crear directorio si no existe
            $targetDir = dirname($targetPath);
            if (! $this->filesystem->exists($targetDir)) {
                $this->filesystem->mkdir($targetDir);
            }

            // Copiar archivo
            $this->filesystem->copy($file->getRealPath(), $targetPath);
        }
    }

    private function applyStackConfiguration(string $stack, string $projectPath, array $config): void
    {
        switch ($stack) {
            case 'saas':
                $this->configureSaasStack($projectPath, $config);
                break;
            case 'api':
                $this->configureApiStack($projectPath, $config);
                break;
            case 'inertia':
                $this->configureInertiaStack($projectPath, $config);
                break;
            case 'livewire':
                $this->configureLivewireStack($projectPath, $config);
                break;
            default:
                $this->configureDefaultStack($projectPath, $config);
                break;
        }
    }

    private function configureSaasStack(string $projectPath, array $config): void
    {
        // Configuraciones específicas para el stack SAAS

        // Activar automáticamente features necesarias para SAAS
        $config['minio'] = true;
        $config['queue'] = true;
        $config['auth'] = true;
        $config['api'] = true;

        // Crear archivos adicionales específicos para SAAS
        $this->createSaasSpecificFiles($projectPath, $config);

        // Configurar variables de entorno específicas para SAAS
        $this->configureSaasEnvironment($projectPath, $config);
    }

    private function createSaasSpecificFiles(string $projectPath, array $config): void
    {
        // Crear Makefile específico para SAAS
        $makefileContent = $this->getSaasMakefileContent($config);
        file_put_contents($projectPath.'/Makefile', $makefileContent);

        // Crear README específico para SAAS
        $readmeContent = $this->getSaasReadmeContent($config);
        file_put_contents($projectPath.'/README.md', $readmeContent);

        // Crear script de inicialización
        $initScript = $this->getSaasInitScript($config);
        file_put_contents($projectPath.'/init-saas.sh', $initScript);
        chmod($projectPath.'/init-saas.sh', 0755);
    }

    private function configureSaasEnvironment(string $projectPath, array $config): void
    {
        $envPath = $projectPath.'/.env.example';

        if ($this->filesystem->exists($envPath)) {
            $envContent = file_get_contents($envPath);

            if ($envContent !== false) {
                // Agregar configuraciones específicas de SAAS si no existen
                $saasConfig = [
                    '# SAAS Configuration',
                    'SAAS_ENABLED=true',
                    'MULTI_TENANT=true',
                    'TENANT_COLUMN=tenant_id',
                    '',
                    '# MinIO Configuration (already included in template)',
                    '# Redis Configuration (already included in template)',
                    '# MySQL Configuration (already included in template)',
                    '',
                    '# Additional SAAS Features',
                    'ENABLE_SUBSCRIPTIONS=true',
                    'ENABLE_BILLING=true',
                    'ENABLE_ANALYTICS=true',
                    'ENABLE_NOTIFICATIONS=true',
                    '',
                ];

                // Solo agregar si no existe la configuración SAAS
                if (strpos($envContent, 'SAAS_ENABLED') === false) {
                    $envContent .= "\n".implode("\n", $saasConfig);
                    file_put_contents($envPath, $envContent);
                }
            }
        }
    }

    private function configureApiStack(string $projectPath, array $config): void
    {
        // Configuraciones específicas para API
        $config['api'] = true;
    }

    private function configureInertiaStack(string $projectPath, array $config): void
    {
        // Configuraciones específicas para Inertia
        // Aquí se pueden agregar configuraciones específicas
    }

    private function configureLivewireStack(string $projectPath, array $config): void
    {
        // Configuraciones específicas para Livewire
        // Aquí se pueden agregar configuraciones específicas
    }

    private function configureDefaultStack(string $projectPath, array $config): void
    {
        // Configuraciones para el stack por defecto
        // Aquí se pueden agregar configuraciones específicas
    }

    private function getSaasMakefileContent(array $config): string
    {
        return <<<MAKEFILE
# TacoCraft SAAS - Makefile
# Generated for: {$config['name']}

.PHONY: help install serve down build clean logs shell test backup

# Colors
RED=\033[0;31m
GREEN=\033[0;32m
YELLOW=\033[1;33m
BLUE=\033[0;34m
NC=\033[0m # No Color

help: ## Show this help message
	@echo "\$(GREEN)TacoCraft SAAS - {$config['name']}\$(NC)"
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*\$\$' \$(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \$(BLUE)%-15s\$(NC) %s\n", \$\$1, \$\$2}'

install: ## Install and setup the project
	@echo "\$(YELLOW)🌮 Installing TacoCraft SAAS...\$(NC)"
	docker-compose up -d mysql redis minio
	@echo "\$(YELLOW)⏳ Waiting for services to be ready...\$(NC)"
	sleep 10
	docker-compose up -d
	@echo "\$(GREEN)✅ Installation complete!\$(NC)"
	@echo "\$(BLUE)🌐 Your app will be available at: https://{$config['domain']}\$(NC)"

serve: ## Start all services
	@echo "\$(YELLOW)🚀 Starting services...\$(NC)"
	docker-compose up -d
	@echo "\$(GREEN)✅ Services started!\$(NC)"
	@echo "\$(BLUE)🌐 App: https://{$config['domain']}\$(NC)"
	@echo "\$(BLUE)📧 MailHog: http://localhost:8025\$(NC)"
	@echo "\$(BLUE)💾 MinIO Console: http://localhost:9001\$(NC)"

down: ## Stop all services
	@echo "\$(YELLOW)🛑 Stopping services...\$(NC)"
	docker-compose down
	@echo "\$(GREEN)✅ Services stopped!\$(NC)"

build: ## Build Docker images
	@echo "\$(YELLOW)🔨 Building images...\$(NC)"
	docker-compose build --no-cache
	@echo "\$(GREEN)✅ Build complete!\$(NC)"

clean: ## Clean up containers, images, and volumes
	@echo "\$(YELLOW)🧹 Cleaning up...\$(NC)"
	docker-compose down -v --remove-orphans
	docker system prune -f
	@echo "\$(GREEN)✅ Cleanup complete!\$(NC)"

logs: ## Show logs for all services
	docker-compose logs -f

logs-app: ## Show logs for Laravel app
	docker-compose logs -f app

logs-nginx: ## Show logs for Nginx
	docker-compose logs -f nginx

logs-mysql: ## Show logs for MySQL
	docker-compose logs -f mysql

logs-redis: ## Show logs for Redis
	docker-compose logs -f redis

logs-minio: ## Show logs for MinIO
	docker-compose logs -f minio

shell: ## Access Laravel app shell
	docker-compose exec app bash

shell-mysql: ## Access MySQL shell
	docker-compose exec mysql mysql -u root -p

shell-redis: ## Access Redis shell
	docker-compose exec redis redis-cli

test: ## Run tests
	docker-compose exec app php artisan test

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

migrate-fresh: ## Fresh migration with seeding
	docker-compose exec app php artisan migrate:fresh --seed

queue-work: ## Start queue worker
	docker-compose exec app php artisan queue:work

queue-restart: ## Restart queue workers
	docker-compose exec app php artisan queue:restart

backup: ## Create database backup
	@echo "\$(YELLOW)💾 Creating backup...\$(NC)"
	docker-compose exec mysql mysqldump -u root -p{$config['name']}_db > backup_\$(shell date +%Y%m%d_%H%M%S).sql
	@echo "\$(GREEN)✅ Backup created!\$(NC)"

optimize: ## Optimize Laravel application
	docker-compose exec app php artisan optimize
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

status: ## Show services status
	docker-compose ps

MAKEFILE;
    }

    private function getSaasReadmeContent(array $config): string
    {
        return <<<README
# {$config['name']} - TacoCraft SAAS

🌮 **Laravel SAAS MVP** generado con TacoCraft

## 🚀 Características

- **Laravel 11** con PHP {$config['php']}
- **Docker** con Docker Compose
- **MySQL** para base de datos principal
- **Redis** para cache, sesiones y colas
- **MinIO** para almacenamiento S3-compatible
- **Nginx** con cache optimizado para MinIO
- **MailHog** para testing de emails
- **Queue Workers** para procesamiento en background
- **Configuración multi-tenant** lista para usar

## 📋 Requisitos

- Docker & Docker Compose
- Git
- Make (opcional, pero recomendado)

## 🛠️ Instalación

### Opción 1: Script automático
```bash
./start.sh
```

### Opción 2: Manual
```bash
# Instalar y configurar
make install

# Iniciar servicios
make serve
```

### Opción 3: Docker Compose directo
```bash
# Iniciar servicios
docker-compose up -d

# Ver logs
docker-compose logs -f
```

## 🌐 URLs de Acceso

- **Aplicación**: https://{$config['domain']}
- **MailHog**: http://localhost:8025
- **MinIO Console**: http://localhost:9001
  - Usuario: `minioadmin`
  - Contraseña: `minioadmin`

## 📁 Estructura del Proyecto

```
{$config['name']}/
├── docker/                 # Configuraciones Docker
│   ├── nginx/             # Configuración Nginx
│   └── php/               # Configuración PHP
├── src/                   # Código Laravel
├── docker-compose.yml     # Servicios Docker
├── Makefile              # Comandos útiles
└── README.md             # Este archivo
```

## 🔧 Comandos Útiles

```bash
# Ver ayuda
make help

# Acceder al contenedor de Laravel
make shell

# Ver logs
make logs

# Ejecutar migraciones
make migrate

# Ejecutar tests
make test

# Crear backup
make backup

# Optimizar aplicación
make optimize

# Parar servicios
make down

# Limpiar todo
make clean
```

## 🗄️ Base de Datos

### Configuración
- **Host**: mysql
- **Puerto**: 3306
- **Base de datos**: {$config['name']}_db
- **Usuario**: root
- **Contraseña**: root_password

### Migraciones
```bash
# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Migración fresca con seeders
docker-compose exec app php artisan migrate:fresh --seed
```

## 💾 MinIO (Almacenamiento)

### Configuración
- **Endpoint**: http://minio:9000
- **Console**: http://localhost:9001
- **Access Key**: minioadmin
- **Secret Key**: minioadmin
- **Bucket**: {$config['name']}-bucket

### Uso en Laravel
```php
// Subir archivo
Storage::disk('minio')->put('path/file.jpg', \$fileContent);

// Obtener URL pública
\$url = Storage::disk('minio')->url('path/file.jpg');
```

## 🔄 Redis (Cache y Colas)

### Configuración
- **Host**: redis
- **Puerto**: 6379
- **Bases de datos**:
  - 0: Default
  - 1: Cache
  - 2: Sessions
  - 3: Queues

### Colas
```bash
# Iniciar worker
docker-compose exec app php artisan queue:work

# Reiniciar workers
docker-compose exec app php artisan queue:restart
```

## 📧 MailHog (Testing de Emails)

- **URL**: http://localhost:8025
- Todos los emails enviados por la aplicación aparecerán aquí
- Configuración automática en `.env`

## 🔒 SSL/HTTPS

- Certificados SSL auto-generados para desarrollo
- Configuración automática en Nginx
- Redirección HTTP → HTTPS

## 🚀 Despliegue

### Desarrollo
```bash
# Modo desarrollo (con hot reload)
make serve
```

### Producción
```bash
# Optimizar para producción
make optimize

# Usar variables de entorno de producción
cp .env.example .env.production
# Editar .env.production con valores reales
```

## 🐛 Troubleshooting

### Problemas Comunes

1. **Puerto ocupado**
   ```bash
   # Cambiar puertos en docker-compose.yml
   # O parar servicios que usen los puertos
   ```

2. **Permisos de archivos**
   ```bash
   # Arreglar permisos
   sudo chown -R \$USER:\$USER src/
   chmod -R 755 src/storage src/bootstrap/cache
   ```

3. **Base de datos no conecta**
   ```bash
   # Verificar que MySQL esté corriendo
   docker-compose ps
   
   # Ver logs de MySQL
   make logs-mysql
   ```

4. **MinIO no accesible**
   ```bash
   # Verificar configuración
   make logs-minio
   
   # Acceder al console
   open http://localhost:9001
   ```

## 📚 Documentación Adicional

- [Laravel Documentation](https://laravel.com/docs)
- [Docker Compose](https://docs.docker.com/compose/)
- [MinIO Documentation](https://docs.min.io/)
- [Redis Documentation](https://redis.io/documentation)

## 🤝 Contribuir

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir Pull Request

## 📄 Licencia

Este proyecto está bajo la licencia MIT.

---

**Generado con ❤️ por TacoCraft** 🌮
README;
    }

    private function getSaasInitScript(array $config): string
    {
        return <<<SCRIPT
#!/bin/bash

# TacoCraft SAAS Initialization Script
# Generated for: {$config['name']}

set -e

echo "🌮 Initializing TacoCraft SAAS: {$config['name']}"
echo "======================================"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "\${BLUE}[INFO]\${NC} \$1"
}

log_success() {
    echo -e "\${GREEN}[SUCCESS]\${NC} \$1"
}

log_warning() {
    echo -e "\${YELLOW}[WARNING]\${NC} \$1"
}

log_error() {
    echo -e "\${RED}[ERROR]\${NC} \$1"
}

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    log_error "Docker is not running. Please start Docker and try again."
    exit 1
fi

log_success "Docker is running"

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    log_error "docker-compose is not installed. Please install it and try again."
    exit 1
fi

log_success "docker-compose is available"

# Create .env file if it doesn't exist
if [ ! -f "src/.env" ]; then
    log_info "Creating .env file from .env.example"
    cp src/.env.example src/.env
    
    # Generate application key
    log_info "Generating application key"
    docker-compose run --rm app php artisan key:generate
else
    log_info ".env file already exists"
fi

# Start services
log_info "Starting services..."
docker-compose up -d mysql redis minio

# Wait for services to be ready
log_info "Waiting for services to be ready..."
sleep 15

# Start remaining services
log_info "Starting remaining services..."
docker-compose up -d

# Wait a bit more
sleep 10

# Run migrations
log_info "Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Create storage link
log_info "Creating storage link..."
docker-compose exec -T app php artisan storage:link

# Clear and cache config
log_info "Optimizing application..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Setup MinIO bucket
log_info "Setting up MinIO bucket..."
docker-compose exec -T app php artisan tinker --execute="
    use Illuminate\Support\Facades\Storage;
    try {
        Storage::disk('minio')->makeDirectory('restaurants');
        echo 'MinIO bucket configured successfully';
    } catch (Exception \$e) {
        echo 'MinIO setup will be completed on first use';
    }
"

echo ""
log_success "🎉 TacoCraft SAAS initialization complete!"
echo ""
echo -e "\${BLUE}🌐 Your application is available at:\${NC}"
echo -e "   ➜ App: https://{$config['domain']}"
echo -e "   ➜ MailHog: http://localhost:8025"
echo -e "   ➜ MinIO Console: http://localhost:9001"
echo ""
echo -e "\${YELLOW}📋 Next steps:\${NC}"
echo "   1. Add {$config['domain']} to your /etc/hosts file"
echo "   2. Visit your application and start building!"
echo "   3. Check the README.md for more information"
echo ""
echo -e "\${GREEN}¡Buen provecho! 🌮\${NC}"
SCRIPT;
    }

    public function getAvailableStacks(): array
    {
        $stacks = [];
        $finder = new Finder;

        if ($this->filesystem->exists($this->templatesPath)) {
            $finder->directories()->in($this->templatesPath)->depth(0);

            foreach ($finder as $directory) {
                $stacks[] = $directory->getFilename();
            }
        }

        return $stacks;
    }

    public function stackExists(string $stack): bool
    {
        return $this->filesystem->exists($this->templatesPath.'/'.$stack);
    }
}
