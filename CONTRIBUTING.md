# Contribuir a TacoCraft 🌮

¡Gracias por tu interés en contribuir a TacoCraft! Este documento te guiará a través del proceso de contribución.

## 🚀 Cómo Contribuir

### Reportar Bugs

Si encuentras un bug, por favor:

1. Verifica que no haya sido reportado anteriormente en [Issues](https://github.com/Nogthings/tacocraft/issues)
2. Crea un nuevo issue con:
   - Descripción clara del problema
   - Pasos para reproducir el bug
   - Comportamiento esperado vs actual
   - Versión de TacoCraft
   - Sistema operativo
   - Versión de PHP
   - Logs relevantes

### Sugerir Nuevas Funcionalidades

Para sugerir nuevas funcionalidades:

1. Abre un issue con la etiqueta "enhancement"
2. Describe claramente la funcionalidad propuesta
3. Explica por qué sería útil
4. Proporciona ejemplos de uso si es posible

### Contribuir con Código

#### Configuración del Entorno de Desarrollo

1. **Fork el repositorio**
   ```bash
   git clone https://github.com/tu-usuario/tacocraft.git
   cd tacocraft
   ```

2. **Instalar dependencias**
   ```bash
   composer install
   ```

3. **Verificar que todo funciona**
   ```bash
   php bin/tacocraft --version
   composer test
   ```

#### Flujo de Trabajo

1. **Crear una rama para tu feature**
   ```bash
   git checkout -b feature/nombre-de-tu-feature
   ```

2. **Hacer tus cambios**
   - Sigue las convenciones de código existentes
   - Agrega tests para nuevas funcionalidades
   - Actualiza la documentación si es necesario

3. **Ejecutar tests y análisis**
   ```bash
   composer test
   composer analyse
   composer format
   ```

4. **Commit tus cambios**
   ```bash
   git add .
   git commit -m "feat: descripción clara del cambio"
   ```

5. **Push y crear Pull Request**
   ```bash
   git push origin feature/nombre-de-tu-feature
   ```

#### Convenciones de Commit

Usamos [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` Nueva funcionalidad
- `fix:` Corrección de bug
- `docs:` Cambios en documentación
- `style:` Cambios de formato (no afectan funcionalidad)
- `refactor:` Refactorización de código
- `test:` Agregar o modificar tests
- `chore:` Tareas de mantenimiento

Ejemplos:
```
feat: add support for PHP 8.3
fix: resolve docker-compose template variables
docs: update installation instructions
```

## 🧪 Testing

### Ejecutar Tests

```bash
# Todos los tests
composer test

# Tests unitarios
composer test:unit

# Tests de funcionalidad
composer test:feature
```

### Escribir Tests

- Usa Pest para nuevos tests
- Coloca tests unitarios en `tests/Unit/`
- Coloca tests de funcionalidad en `tests/Feature/`
- Asegúrate de que todos los tests pasen

### Ejemplo de Test

```php
test('can create new project', function () {
    $generator = new ProjectGenerator();
    
    expect($generator->create('test-project', 'saas'))
        ->toBeTrue();
        
    expect(file_exists('test-project/docker-compose.yml'))
        ->toBeTrue();
});
```

## 📝 Documentación

### Actualizar Documentación

- Actualiza el README.md si cambias funcionalidad
- Agrega ejemplos de uso para nuevas features
- Documenta nuevos templates o comandos
- Actualiza el CHANGELOG.md

### Estilo de Documentación

- Usa emojis para hacer la documentación más amigable
- Incluye ejemplos de código
- Mantén las explicaciones claras y concisas
- Usa español para documentación de usuario

## 🎨 Estilo de Código

### PHP

- Seguimos PSR-12
- Usa Laravel Pint para formateo: `composer format`
- Usa PHPStan para análisis estático: `composer analyse`
- Usa type hints siempre que sea posible
- Documenta métodos públicos con PHPDoc

### Estructura de Archivos

```
src/
├── Application.php          # Aplicación principal
├── Commands/               # Comandos CLI
├── Services/               # Lógica de negocio
├── Templates/              # Gestión de templates
└── Utils/                  # Utilidades
```

## 🔍 Code Review

### Criterios de Revisión

- ✅ Funcionalidad correcta
- ✅ Tests que pasen
- ✅ Código bien documentado
- ✅ Sigue convenciones de estilo
- ✅ No rompe funcionalidad existente
- ✅ Documentación actualizada

### Proceso de Review

1. El maintainer revisará tu PR
2. Se pueden solicitar cambios
3. Una vez aprobado, se hará merge
4. Tu contribución aparecerá en el próximo release

## 🏷️ Releases

### Versionado

Usamos [Semantic Versioning](https://semver.org/):

- `MAJOR`: Cambios incompatibles
- `MINOR`: Nueva funcionalidad compatible
- `PATCH`: Correcciones de bugs

### Proceso de Release

1. Actualizar CHANGELOG.md
2. Crear tag de versión
3. Publicar en Packagist
4. Crear release en GitHub

## 🤝 Código de Conducta

### Nuestros Valores

- **Respeto**: Trata a todos con respeto y profesionalismo
- **Inclusión**: Todos son bienvenidos, sin importar su experiencia
- **Colaboración**: Trabajamos juntos para mejorar TacoCraft
- **Aprendizaje**: Todos estamos aquí para aprender y crecer

### Comportamiento Esperado

- Usa lenguaje inclusivo y profesional
- Respeta diferentes puntos de vista
- Acepta críticas constructivas
- Enfócate en lo que es mejor para la comunidad

## 📞 Contacto

Si tienes preguntas sobre contribuir:

- Abre un issue en GitHub
- Contacta al maintainer: oscargalvez812@gmail.com

---

¡Gracias por contribuir a TacoCraft! 🌮❤️