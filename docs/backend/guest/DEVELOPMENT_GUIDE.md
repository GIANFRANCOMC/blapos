# Guest - Guía de desarrollo

## Principios

- Todo input público es no confiable.
- La empresa siempre proviene de `company_slug` resuelto.
- El contrato devuelve solo la información necesaria para el visitante.
- Las reglas compartidas viven en servicios de negocio; las consultas públicas específicas viven en `app/Services/Guest`.
- Los errores internos nunca llegan directamente al cliente.

## Lista de implementación

1. Ruta en `routes/Guest`.
2. `company.exists` y middleware adicional requerido.
3. FormRequest o validación dedicada.
4. Modelo/servicio con selección explícita de columnas.
5. Rate limit registrado en `RouteServiceProvider` y configurado en `config/public_access.php`.
6. Registro mínimo de IP/agente cuando exista obligación de trazabilidad.
7. Respuesta sin IDs o secretos innecesarios.
8. Documentación en `docs/backend/guest/modules`.
9. Documentar los cambios visuales del módulo Guest y consultar los [estándares de interfaz](../../frontend/fundamentos/ESTANDARES_UI.md).

## Verificación mínima

- empresa inexistente o inactiva;
- sucursal ajena a la empresa;
- payload incompleto o manipulado;
- límite por minuto y límite prolongado;
- firma o credencial vencida;
- respuesta sin campos internos;
- reintento idempotente cuando aplique.
