# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Preparación para publicación en Packagist
- Documentación completa del proyecto
- Archivo LICENSE con licencia MIT

## [1.0.0] - 2025-01-30

### Added
- 🎉 Lanzamiento inicial de TacoCraft
- CLI para generar proyectos Laravel con Docker
- Template SAAS completo con:
  - Docker Compose preconfigurado
  - MySQL 8.0 optimizado
  - Redis para cache y sesiones
  - MinIO para almacenamiento de archivos
  - Nginx con proxy reverso y cache
  - MailHog para testing de emails
  - PHP 8.2 con extensiones optimizadas
  - Supervisor para gestión de procesos
- Comando `new` para crear proyectos
- Templates disponibles:
  - `default` - Proyecto Laravel básico
  - `saas` - Aplicación SaaS completa
  - `api` - API REST
  - `inertia` - SPA con Inertia.js
  - `livewire` - Aplicación con Livewire
- Servicios PHP para manejo de imágenes con MinIO
- Scripts de deployment automatizado
- Configuraciones de seguridad básicas
- Documentación completa

### Technical Details
- PHP 8.1+ requerido
- Symfony Console para CLI
- Docker y Docker Compose
- Arquitectura modular y extensible
- Tests automatizados
- Análisis estático con PHPStan
- Formateo de código con Laravel Pint

### Security
- Configuraciones de seguridad en Nginx
- Headers de seguridad implementados
- Configuración SSL/TLS
- Firewall básico
- Backup automatizado

[Unreleased]: https://github.com/oscargalvez/tacocraft/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/oscargalvez/tacocraft/releases/tag/v1.0.0