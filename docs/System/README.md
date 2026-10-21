# System - Plataforma Principal

`System` es la plataforma interna usada por empresas y sus usuarios autenticados. Representa la operacion principal del negocio: configuracion de empresa, usuarios, clientes, ventas, membresias, asistencias, catalogos, inventario, activos, biometricos, dashboard y reportes.

## Separacion De Responsabilidad

System no debe asumir comportamiento de visitantes publicos. Si una funcionalidad esta pensada para clientes finales, enlaces publicos, formularios sin login o servicios expuestos por `company_slug`, debe documentarse y desarrollarse en `Guest`.

## Estructura Real En Codigo

- Controladores: `app/Http/Controllers/System`
- Modelos: `app/Models/System`
- Requests: `app/Http/Requests/System`
- Servicios: `app/Services/System`
- Rutas: `routes/System`
- Vistas Blade: `resources/views/System`
- Vue: `resources/js/System`

## Lectura Recomendada

1. [ARCHITECTURE.md](ARCHITECTURE.md)
2. [DEVELOPMENT_GUIDE.md](DEVELOPMENT_GUIDE.md)
3. [BACKEND_CONVENTIONS.md](BACKEND_CONVENTIONS.md)
4. [CODE_STYLE.md](CODE_STYLE.md)
5. [TENANT_NAVIGATION.md](TENANT_NAVIGATION.md)
6. [TABLES.md](TABLES.md)
7. [DATABASE_INSTALLATION.md](DATABASE_INSTALLATION.md)
8. [TESTING.md](TESTING.md)
9. [DATABASE_OPTIMIZATION.md](DATABASE_OPTIMIZATION.md)
10. [modules/00_menu_order.md](modules/00_menu_order.md)
11. Modulos numerados en [modules](modules)
12. Decisiones de evolucion en [new_requirements](new_requirements)

## Modulos Por Menu

El menú se consulta desde `menu_categories`, `sections`, `menu_groups` y `sub_sections`. Visualmente utiliza una barra de módulos basada en `sections` y un panel contextual basado en `menu_groups` y `sub_sections`.

Las cuatro categorías se conservan para organización interna y aprovisionamiento, pero ya no se muestran como encabezados permanentes:

1. Principal.
2. Operaciones.
3. Gestión.
4. Administración.

Dentro de cada seccion, los archivos de `modules` siguen la numeracion del menu y luego agregan modulos tecnicos de soporte.

## Generalidades Transversales

Antes de tocar cualquier modulo System, revisar [../GENERALIDADES.md](../GENERALIDADES.md). Ese archivo concentra criterios compartidos de branding, formularios, modales, cache, migraciones, multiempresa y documentacion.

## Criterios De Mantenimiento

- Mantener el catálogo de módulos en las tablas de navegación. `SystemNavigationSeeder` inicializa bases vacías y consolida los grupos heredados documentados, sin cambiar los identificadores de las rutas.
- Cada modulo documenta tablas, rutas, reglas y comportamiento backend vigente.
- Evitar duplicar criterios visuales aqui; si aplica a varios modulos, moverlo a `GENERALIDADES.md`.
- Las decisiones visuales transversales se mantienen en `GENERALIDADES.md`; cada pantalla documenta sus mejoras en su módulo.
