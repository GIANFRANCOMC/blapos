# Guest - Portal público

`Guest` representa las superficies para visitantes y clientes finales. No es el panel administrativo y no debe exponer reglas internas, stock, usuarios, secretos ni configuraciones de System.

## Estructura

- Controladores: `app/Http/Controllers/Guest`
- Modelos públicos: `app/Models/Guest`
- Servicios públicos: `app/Services/Guest`
- Rutas: `routes/Guest`
- Blade: `resources/views/Guest`
- Vue: `resources/js/Guest`

## Rutas actuales

- `{company_slug}/home`
- `{company_slug}/book_complaints`
- `{company_slug}/tracking_attendances`
- `{company_slug}/biometric_devices`

`company.exists` resuelve únicamente empresas activas dentro del tenant actual. Las operaciones sensibles añaden firma, capacidad temporal, credencial de dispositivo o rate limit según el recurso.

## Lectura recomendada

1. [ARCHITECTURE.md](ARCHITECTURE.md)
2. [DEVELOPMENT_GUIDE.md](DEVELOPMENT_GUIDE.md)
3. [TABLES.md](../datos/guest/TABLES.md)
4. [modules](modules)
5. [new_requirements](new_requirements)

Las reglas transversales viven en [GENERALIDADES.md](../desarrollo/GENERALIDADES.md). Toda capacidad implementada, sea backend o visual, se documenta en el módulo afectado.
