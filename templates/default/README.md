# TacoCraft Default Template

Este template proporciona un entorno de desarrollo Laravel completo con Docker, incluyendo todos los servicios necesarios para desarrollo moderno.

## 🚀 Inicio Rápido

### 1. Iniciar el Proyecto

**En Linux/Mac:**
```bash
./start.sh
```

**En Windows:**
```cmd
start.bat
```

### 2. Configurar Features de Laravel

Después de que los contenedores estén corriendo:

**En Linux/Mac:**
```bash
./setup.sh
```

**En Windows:**
```powershell
.\setup.ps1
```

### 3. Configurar tu Aplicación

1. Edita el archivo `.env` en el directorio `src/`
2. Configura tu base de datos y otros servicios
3. ¡Comienza a desarrollar!

## 📦 Servicios Incluidos

| Servicio | Puerto | Descripción |
|----------|--------|-------------|
| **Nginx** | 80, 443 | Servidor web con SSL |
| **PHP 8.2** | - | Aplicación Laravel |
| **MySQL** | 3306 | Base de datos |
| **Redis** | 6379 | Cache y sesiones |
| **MinIO** | 9000, 9001 | Almacenamiento S3-compatible |
| **MailHog** | 8025 | Testing de emails |
| **Supervisor** | - | Gestión de procesos |

## 🌐 URLs de Acceso

- **Aplicación:** http://localhost (HTTPS: https://localhost)
- **MailHog:** http://localhost:8025
- **MinIO Console:** http://localhost:9001

## 🔧 Comandos Útiles

### Gestión de Contenedores
```bash
# Iniciar servicios
docker-compose up -d

# Ver logs
docker-compose logs -f

# Detener servicios
docker-compose down

# Reconstruir contenedores
docker-compose up -d --build
```

### Comandos de Laravel
```bash
# Ejecutar comandos artisan
docker-compose exec app php artisan [comando]

# Instalar dependencias
docker-compose exec app composer install

# Ejecutar migraciones
docker-compose exec app php artisan migrate

# Acceder al contenedor
docker-compose exec app bash
```

### Comandos de NPM
```bash
# Instalar dependencias de frontend
docker-compose exec app npm install

# Compilar assets
docker-compose exec app npm run dev

# Watch mode para desarrollo
docker-compose exec app npm run watch
```

## 🔐 Configuración SSL

El template incluye certificados SSL autofirmados para desarrollo. Para producción:

1. Reemplaza los certificados en `docker/nginx/ssl/`
2. Actualiza la configuración de Nginx si es necesario

## 📁 Estructura del Proyecto

```
.
├── docker/
│   └── nginx/
│       ├── nginx.conf
│       └── ssl/
├── src/                 # Código de Laravel
├── docker-compose.yml   # Configuración de servicios
├── start.sh            # Script de inicio (Linux/Mac)
├── start.bat           # Script de inicio (Windows)
├── setup.sh            # Setup de features (Linux/Mac)
├── setup.ps1           # Setup de features (Windows)
└── tacocraft-features.php # Configuración de features
```

## 🐛 Solución de Problemas

### Los contenedores no inician
```bash
# Verificar Docker
docker --version
docker-compose --version

# Limpiar contenedores
docker-compose down -v
docker system prune -f
```

### Problemas de permisos
```bash
# En Linux/Mac
sudo chown -R $USER:$USER src/
chmod -R 775 src/storage src/bootstrap/cache

# En Windows (ejecutar como administrador)
icacls src /grant Everyone:F /T
```

### Laravel no se instala
```bash
# Instalar manualmente
docker-compose exec app composer create-project laravel/laravel . --prefer-dist
```

### Base de datos no conecta
1. Verifica que MySQL esté corriendo: `docker-compose ps`
2. Revisa la configuración en `src/.env`:
   ```
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=laravel
   DB_PASSWORD=password
   ```

## 📚 Recursos Adicionales

- [Documentación de Laravel](https://laravel.com/docs)
- [Docker Compose Reference](https://docs.docker.com/compose/)
- [TacoCraft GitHub](https://github.com/tacocraft/tacocraft)

## 🤝 Soporte

Si encuentras problemas:

1. Revisa esta documentación
2. Verifica los logs: `docker-compose logs`
3. Abre un issue en GitHub
4. Consulta la comunidad de Laravel

¡Feliz desarrollo! 🌮✨