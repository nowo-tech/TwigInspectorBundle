# Guía de Contribución

¡Gracias por tu interés en contribuir a Twig Inspector Bundle! Este documento proporciona directrices para contribuir al proyecto.

## Código de Conducta

Este proyecto se adhiere a un código de conducta. Al participar, se espera que mantengas este código. Por favor, reporta comportamientos inaceptables a hectorfranco@nowo.com.

## ¿Cómo puedo contribuir?

### Reportar Bugs

Si encuentras un bug, por favor:

1. **Verifica que el bug no haya sido reportado ya** en los [issues](https://github.com/nowo-tech/twig-inspector-bundle/issues)
2. **Crea un nuevo issue** con:
   - Un título descriptivo
   - Pasos para reproducir el problema
   - Comportamiento esperado vs. comportamiento actual
   - Versión de PHP, Symfony y del bundle
   - Capturas de pantalla si es relevante

### Sugerir Mejoras

Las sugerencias de mejoras son bienvenidas:

1. **Verifica que la mejora no haya sido sugerida** en los [issues](https://github.com/nowo-tech/twig-inspector-bundle/issues)
2. **Crea un nuevo issue** con:
   - Un título descriptivo
   - Descripción detallada de la mejora propuesta
   - Casos de uso y beneficios
   - Posibles implementaciones (si las tienes)

### Contribuir con Código

#### Configuración del Entorno de Desarrollo

1. **Fork el repositorio** en GitHub
2. **Clona tu fork**:
   ```bash
   git clone https://github.com/tu-usuario/twig-inspector-bundle.git
   cd twig-inspector-bundle
   ```
3. **Instala las dependencias**:
   ```bash
   # Con Docker (recomendado)
   make install
   
   # Sin Docker
   composer install
   ```

#### Estándares de Código

El proyecto sigue estos estándares:

- **PSR-12**: Estilo de código PHP
- **PHP 8.1+**: Características modernas de PHP
- **Type hints estrictos**: `declare(strict_types=1);` en todos los archivos
- **PHP-CS-Fixer**: Se usa para mantener la consistencia del código

**Antes de hacer commit**:

```bash
# Verificar el estilo de código
make cs-check
# o
composer cs-check

# Corregir automáticamente el estilo
make cs-fix
# o
composer cs-fix
```

#### Tests

**El proyecto requiere 100% de cobertura de código**. Todos los tests deben pasar antes de hacer merge.

```bash
# Ejecutar todos los tests
make test
# o
composer test

# Ejecutar tests con cobertura
make test-coverage
# o
composer test-coverage

# Ver el reporte de cobertura
open coverage/index.html
```

**Estructura de tests**:
- Los tests deben estar en el directorio `tests/`
- Cada clase debe tener su test correspondiente
- Los tests deben ser descriptivos y cubrir casos edge
- Usa mocks cuando sea apropiado

#### Proceso de Pull Request

1. **Crea una rama** desde `main`:
   ```bash
   git checkout -b feature/mi-nueva-funcionalidad
   # o
   git checkout -b fix/mi-correccion
   ```

2. **Haz tus cambios**:
   - Escribe código limpio y bien documentado
   - Añade tests para nuevas funcionalidades
   - Asegúrate de que todos los tests pasen
   - Verifica que la cobertura sea 100%
   - Ejecuta `make qa` para verificar todo

3. **Commit tus cambios**:
   ```bash
   git add .
   git commit -m "feat: descripción de la funcionalidad"
   # o
   git commit -m "fix: descripción de la corrección"
   ```
   
   **Convenciones de commits**:
   - `feat:` Nueva funcionalidad
   - `fix:` Corrección de bug
   - `docs:` Cambios en documentación
   - `test:` Añadir o modificar tests
   - `refactor:` Refactorización de código
   - `style:` Cambios de formato (no afectan funcionalidad)
   - `chore:` Tareas de mantenimiento

4. **Push a tu fork**:
   ```bash
   git push origin feature/mi-nueva-funcionalidad
   ```

5. **Crea un Pull Request** en GitHub:
   - Describe claramente los cambios
   - Menciona cualquier issue relacionado
   - Añade capturas de pantalla si es relevante
   - Asegúrate de que el CI pase

#### Checklist antes de hacer PR

- [ ] El código sigue los estándares PSR-12
- [ ] Se ejecutó `make cs-fix` (o `composer cs-fix`)
- [ ] Todos los tests pasan (`make test`)
- [ ] La cobertura de código es 100% (`make test-coverage`)
- [ ] Se añadieron tests para nuevas funcionalidades
- [ ] La documentación está actualizada (si es necesario)
- [ ] El CHANGELOG.md está actualizado (si es necesario)
- [ ] El código está bien comentado
- [ ] No hay warnings o errores de PHPStan/Psalm (si se usan)

## Estructura del Proyecto

```
twig-inspector-bundle/
├── src/                    # Código fuente del bundle
│   ├── Controller/         # Controladores
│   ├── DataCollector/      # Data collectors para Web Profiler
│   ├── DependencyInjection/ # Configuración del bundle
│   ├── Resources/          # Recursos (templates, assets)
│   └── Twig/               # Extensiones y nodos de Twig
├── tests/                  # Tests
├── demo/                   # Proyectos demo (Symfony 6.4, 7.0, 8.0)
├── .github/                # Configuración de GitHub
└── docs/                   # Documentación adicional
```

## Desarrollo de Assets

El bundle incluye assets TypeScript y SCSS:

```bash
# Instalar dependencias de Node
npm install

# Build para producción
npm run build
# o
make build-assets

# Build para desarrollo
npm run build:dev
# o
make build-assets-dev

# Modo watch
npm run watch
# o
make watch-assets
```

Los archivos compilados se encuentran en `src/Resources/assets/dist/` y deben ser copiados a `src/Resources/views/assets/dist/` para que Twig pueda incluirlos.

## Demos

El proyecto incluye tres demos independientes para probar el bundle con diferentes versiones de Symfony:

- `demo/demo-symfony6/` - Symfony 6.4
- `demo/demo-symfony7/` - Symfony 7.0
- `demo/demo-symfony8/` - Symfony 8.0

Para ejecutar una demo:

```bash
# Instalar dependencias
make install-symfony6  # o install-symfony7, install-symfony8

# Iniciar contenedores
cd demo/demo-symfony6 && docker-compose up -d

# Acceder a la demo
# http://localhost:8001
```

## Preguntas

Si tienes preguntas sobre cómo contribuir, puedes:

- Abrir un issue en GitHub
- Contactar a los mantenedores en hectorfranco@nowo.com

## Reconocimientos

Gracias por contribuir a Twig Inspector Bundle. Tu ayuda hace que este proyecto sea mejor para todos.

