# Backend de Blapos

Consulta estas guías para trabajar con Laravel, reglas de negocio, endpoints, persistencia y aislamiento de datos.

| Área | Guías |
| --- | --- |
| Desarrollo | [Convenciones de controladores, requests, servicios y transacciones](desarrollo/BACKEND_CONVENTIONS.md) · [Estilo PHP](desarrollo/CODE_STYLE.md) · [Pruebas automatizadas](desarrollo/TESTING.md) |
| Datos | [Instalación y aprovisionamiento](datos/DATABASE_INSTALLATION.md) · [Esquema e índices](datos/DATABASE_OPTIMIZATION.md) · [Tablas de System](datos/system/TABLES.md) · [Tablas de Guest](datos/guest/TABLES.md) |
| Tenancy | [Arquitectura multi-tenant](tenancy/MULTITENANT.md) · [Límite tenant–empresa](tenancy/TENANT_COMPANY_BOUNDARY.md) · [Operación de tenants](tenancy/TENANT_OPERATIONS.md) |
| Seguridad | [Autenticación y sesiones](seguridad/SECURITY_AND_AUTH.md) |
| Arquitectura | [Arquitectura general](arquitectura/ARCHITECTURE.md) · [Administración SaaS](arquitectura/PLATFORM_ADMINISTRATION.md) |
| Despliegue | [Instalación y operación](despliegue/INSTALACION.md) · [Subdominios locales](despliegue/LOCAL_SUBDOMAINS.md) · [Subdominios en producción](despliegue/PRODUCTION_SUBDOMAINS.md) |
| Proyecto | [Contexto y mapa de la documentación](proyecto/README.md) |
| Producto y digitalización | [Análisis de negocio y adopción por sucursal](proyecto/ANALISIS_DE_NEGOCIO.md) |

## Contratos compartidos

- [Precisión numérica](desarrollo/GENERALIDADES.md#precisión-numérica) para redondeo, validación, importaciones y exportaciones.
- [Configuración inicial y caché](desarrollo/GENERALIDADES.md#cache-e-initparams) para consultas de arranque y sus dependencias.
- [Reglas por módulo de System](system/README.md) y [Guest](guest/README.md) para estados, permisos y validaciones propias del flujo.
- [Instalación y operación](despliegue/INSTALACION.md) para los procedimientos de entorno y despliegue.

El código se consulta desde [`app`](../../app), [`routes`](../../routes), [`config`](../../config), [`database`](../../database) y [`tests`](../../tests). Para cambios en la presentación que consumen estos contratos, consulta [Frontend](../frontend/README.md).

## Usar la skill con Codex

Abre el proyecto Blapos y escribe `$blapos-backend` junto con la tarea. Puedes pedir «Usa `$blapos-backend` para analizar cómo registrar recepciones parciales entre sucursales. Indica quién confirma, qué cambia en el stock y cómo queda el historial». Usa el verbo «implementa» cuando el alcance incluya cambios de código. [Ver la skill](../../.agents/skills/blapos-backend/SKILL.md).
