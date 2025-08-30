# 🌮 TacoCraft

**Craft Laravel Projects with Mexican Flavor** - Una herramienta moderna de scaffolding para Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tacocraft/tacocraft.svg?style=flat-square)](https://packagist.org/packages/tacocraft/tacocraft)
[![Total Downloads](https://img.shields.io/packagist/dt/tacocraft/tacocraft.svg?style=flat-square)](https://packagist.org/packages/tacocraft/tacocraft)
[![License](https://img.shields.io/packagist/l/tacocraft/tacocraft.svg?style=flat-square)](https://packagist.org/packages/tacocraft/tacocraft)

## 🚀 ¿Qué es TacoCraft?

TacoCraft es una herramienta CLI que te permite crear proyectos Laravel completamente configurados con Docker, incluyendo:

- 🐳 **Docker Compose** preconfigurado
- 🗄️ **MySQL 8.0** optimizado
- 🔴 **Redis** para cache y sesiones
- 📦 **MinIO** para almacenamiento de archivos
- 🌐 **Nginx** con proxy reverso y cache
- 📧 **MailHog** para testing de emails
- ⚡ **PHP 8.2** con extensiones optimizadas
- 🔧 **Supervisor** para gestión de procesos

## 📋 Requisitos

- PHP 8.1 o superior
- Composer
- Docker y Docker Compose
- Git

## 🛠️ Instalación

### Instalación Global (Recomendada)

```bash
composer global require tacocraft/tacocraft
```

### Instalación Local

```bash
composer require --dev tacocraft/tacocraft
```

## 🌮 Uso

### Crear un Nuevo Proyecto

```bash
# Proyecto básico
tacocraft new mi-proyecto

# Proyecto SAAS completo
tacocraft new mi-saas --template=saas

# Proyecto API
tacocraft new mi-api --template=api

# Proyecto con Inertia.js
tacocraft new mi-app --template=inertia

# Proyecto con Livewire
tacocraft new mi-livewire --template=livewire
```

### Opciones Disponibles

```bash
tacocraft new [nombre] [opciones]

Opciones:
  --template=TEMPLATE    Template a usar (default, saas, api, inertia, livewire)
  --force               Sobrescribir directorio existente
  --no-docker          Crear proyecto sin Docker
  --php-version=VERSION Versión de PHP (8.1, 8.2, 8.3)
```

## 📁 Estructura del Proyecto Generado

```
mi-proyecto/
├── docker/
│   ├── nginx/
│   │   └── nginx.conf
│   ├── php/
│   │   ├── Dockerfile
│   │   ├── php.ini
│   │   ├── opcache.ini
│   │   └── supervisord.conf
│   ├── mysql/
│   │   └── my.cnf
│   └── redis/
│       └── redis.conf
├── src/                    # Aplicación Laravel
├── docker-compose.yml
├── Makefile
├── deploy.sh
├── security.conf
└── README.md
```

## 🐳 Comandos Docker

```bash
# Iniciar servicios
docker-compose up -d

# Ver logs
docker-compose logs -f

# Parar servicios
docker-compose down

# Reconstruir contenedores
docker-compose up -d --build
```

## 🔧 Servicios Incluidos

| Servicio | Puerto | Descripción |
|----------|--------|-------------|
| **Nginx** | 80, 443 | Servidor web con proxy reverso |
| **Laravel** | 9000 | Aplicación PHP-FPM |
| **MySQL** | 3306 | Base de datos |
| **Redis** | 6379 | Cache y sesiones |
| **MinIO** | 9000, 9001 | Almacenamiento de archivos |
| **MailHog** | 8025 | Testing de emails |

## 📦 Templates Disponibles

### 🏢 SAAS Template
Perfecto para aplicaciones SaaS con:
- Multi-tenancy preparado
- Sistema de suscripciones
- Panel de administración
- API completa
- Manejo de archivos con MinIO

### 🔌 API Template
Ideal para APIs REST:
- Laravel Sanctum
- Documentación automática
- Rate limiting
- Versionado de API

### ⚡ Inertia Template
Para SPAs modernas:
- Vue.js 3 + Inertia.js
- Tailwind CSS
- TypeScript
- Vite

### 🔥 Livewire Template
Para aplicaciones reactivas:
- Laravel Livewire 3
- Alpine.js
- Tailwind CSS
- Componentes preconstruidos

## 🚀 Deployment

Cada proyecto incluye scripts de deployment:

```bash
# Deployment automático
./deploy.sh

# Configuración de seguridad
./security-setup.sh
```

## 🔒 Seguridad

Todos los proyectos incluyen:
- Configuraciones de seguridad básicas
- Headers de seguridad en Nginx
- Configuración SSL/TLS
- Firewall básico
- Backup automatizado

## 🤝 Contribuir

¡Las contribuciones son bienvenidas! Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 🐛 Reportar Bugs

Si encuentras un bug, por favor [abre un issue](https://github.com/Nogthings/tacocraft/issues) con:

- Descripción del problema
- Pasos para reproducir
- Versión de TacoCraft
- Sistema operativo
- Versión de PHP

## 📝 Changelog

Ver [CHANGELOG.md](CHANGELOG.md) para los cambios recientes.

## 📄 Licencia

Este proyecto está bajo la licencia MIT. Ver [LICENSE](LICENSE) para más detalles.

## 👨‍💻 Autor

**Oscar Galvez**
- Email: oscargalvez812@gmail.com
- GitHub: [@Nogthings](https://github.com/Nogthings)

---

**Hecho con 🌮 y ❤️ en Culiacán, México**

¿Te gusta TacoCraft? ¡Dale una ⭐ en GitHub!
