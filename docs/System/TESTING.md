# Pruebas automatizadas

## Ejecución

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS blapos_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan test --testsuite=Unit
php artisan test
```

La suite unitaria no debe depender de tablas ni registros existentes. Las pruebas funcionales que usan `RefreshDatabase` deben aprovisionar su organización mediante `Tests\Concerns\ProvisionsSystemDatabase`.

## Base de datos de pruebas

- `phpunit.xml` fuerza `DB_DATABASE=blapos_testing`. No retirar este aislamiento ni apuntarlo a una base de desarrollo o producción.
- La base `blapos_testing` debe existir antes de ejecutar pruebas funcionales.
- No asumir que las migraciones insertan una organización, moneda, usuario, sede o almacén.
- Solo la empresa raíz reserva el ID local `1`; no fijar otros IDs autoincrementales entre pruebas porque las transacciones no reinician siempre el contador MySQL.
- Resolver IDs por claves naturales estables como código, correo o nombre dentro de la empresa.
- Usar `CompanyProvisioningService` y `SystemCatalogSyncService` para reproducir el arranque real.
- No ejecutar la suite contra una base con información que deba conservarse: `RefreshDatabase` puede reconstruirla.

El trait `ProvisionsSystemDatabase` crea la empresa raíz de pruebas, sus datos operativos y el administrador `admin@example.test`.

## Caché de configuración

`BaseConfigServiceTest` debe implementar exactamente:

```php
protected static function buildConfig(
    int $companyId,
    string $page,
    ?int $userId = null
): stdClass;
```

`getInitParams` recibe empresa, página y usuario. En servicios no dependientes del usuario, el ID no forma parte de la clave, pero el contrato continúa siendo explícito.

Los servicios con `USER_SCOPED_CACHE=true` registran los usuarios que generaron caché. Las pruebas de invalidación registran el alcance con `registerUserCacheScope`; no crean ni consultan una tabla `users` falsa.

## Autenticación

Blapos no usa el scaffolding estándar de Breeze para registro público, perfil, confirmación o recuperación de contraseña. No deben conservarse pruebas generadas para rutas que el producto no publica.

La cobertura vigente comprueba:

- renderizado del login empresarial;
- autenticación válida resolviendo la única empresa del tenant;
- rechazo de contraseña incorrecta;
- cierre de sesión y redirección al login de la empresa;

Las pruebas HTTP de autenticación deshabilitan únicamente la resolución de tenant para probar el controlador sobre la base temporal ya aprovisionada. La resolución de dominios requiere una base landlord de pruebas independiente y no debe consultar landlord de desarrollo desde esta suite.

## Flujos funcionales

`AttendanceFlowsTest` valida ingreso y salida de clientes, límite diario, jornada del colaborador y bloqueo de jornadas simultáneas. Obtiene moneda, sede, almacén, serie, cliente y usuario por claves estables después del aprovisionamiento.

## Criterio de entrega

Antes de cerrar un cambio transversal:

1. Ejecutar las pruebas dirigidas del flujo.
2. Ejecutar la suite unitaria completa.
3. Ejecutar la suite general.
4. Ejecutar `git diff --check`.
5. Si cambió el frontend, compilar Blade y CSS/Vite según corresponda.

`php artisan view:cache` genera los archivos compilados, pero no garantiza por sí solo que el PHP resultante sea sintácticamente válido. Cuando se modifican directivas Blade anidadas, también debe ejecutarse `php -l` sobre los archivos de `storage/framework/views` o abrir la pantalla afectada durante la validación.

En estructuras anidadas del menú se prefieren bloques explícitos:

```blade
@php
    $menuGroup = $menuGroupItems->first()->menuGroup;
@endphp
```

Evitar `@php(...)` inline dentro de bucles complejos, porque determinadas expresiones pueden producir PHP compilado sin cierre correcto.

Una prueba obsoleta debe actualizarse al dominio real. Solo debe retirarse cuando cubra una ruta o modelo que el producto no posee, dejando documentada la razón.
