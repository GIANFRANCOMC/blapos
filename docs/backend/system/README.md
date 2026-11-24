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
3. [BACKEND_CONVENTIONS.md](../desarrollo/BACKEND_CONVENTIONS.md)
4. [CODE_STYLE.md](../desarrollo/CODE_STYLE.md)
5. [TENANT_NAVIGATION.md](../../frontend/patrones/TENANT_NAVIGATION.md)
6. [TABLES.md](../datos/system/TABLES.md)
7. [DATABASE_INSTALLATION.md](../datos/DATABASE_INSTALLATION.md)
8. [TESTING.md](../desarrollo/TESTING.md)
9. [DATABASE_OPTIMIZATION.md](../datos/DATABASE_OPTIMIZATION.md)
10. [modules/00_menu_order.md](../../frontend/patrones/00_menu_order.md)
11. Modulos numerados en [modules](modules)
12. Decisiones de evolucion en [new_requirements](new_requirements)

## Modulos Por Menu

El menú se consulta desde `menu_categories`, `sections`, `menu_groups` y `sub_sections`. La distribución base reúne Inicio y nueve mundos. El botón de la barra superior controla el panel de grupos y páginas en escritorio y tablet, y la navegación lateral completa en móvil. [Mapa de System](../../frontend/patrones/00_menu_order.md).

Las cuatro categorías se conservan para organización interna y aprovisionamiento, pero ya no se muestran como encabezados permanentes:

1. Principal.
2. Operaciones.
3. Gestión.
4. Administración.

Dentro de cada seccion, los archivos de `modules` siguen la numeracion del menu y luego agregan modulos tecnicos de soporte.

[Rendimiento comercial](modules/28_commercial_performance.md) documenta los indicadores de ventas por periodo, sucursal e ítem y sus permisos.

[Rotación y reposición](modules/29_stock_turnover.md) documenta la consulta por sucursal y almacén, las salidas comerciales netas y las prioridades de reposición.

## Generalidades Transversales

Consulta [Generalidades](../desarrollo/GENERALIDADES.md) para caché, migraciones y contratos de negocio, y la [guía frontend de System](../../frontend/desarrollo/GUIA_SYSTEM.md) para estilos, formularios y modales.

## Criterios De Mantenimiento

- Mantener el catálogo de módulos en las tablas de navegación. `SystemNavigationSeeder` inicializa bases vacías. La aplicación explícita del diseño conserva los IDs de los submódulos en instalaciones existentes.
- Cada modulo documenta tablas, rutas, reglas y comportamiento backend vigente.
- Mantener las decisiones visuales transversales en los [estándares de interfaz](../../frontend/fundamentos/ESTANDARES_UI.md). Cada módulo registra sus decisiones de implementación.
