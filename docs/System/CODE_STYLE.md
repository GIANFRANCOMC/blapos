# Convención de código PHP

## Objetivo

El código debe ser consistente, legible y fácil de revisar. La convención se aplica a `app`, `bootstrap`, `config`, `database`, PHP dentro de `resources`, `routes`, `scripts` y `tests`.

## Reglas

- Usar comillas dobles en cadenas PHP. Si la cadena contiene `$`, barras invertidas o comillas dobles, debe escaparse para conservar exactamente su valor.
- Las condiciones, iteraciones y capturas no llevan espacio antes del paréntesis: `if(condicion) {`, `foreach($items as $item) {`, `catch(Throwable $exception) {`.
- Las directivas Blade siguen la misma regla: `@if($condicion)` y `@foreach($items as $item)`.
- La llave de apertura permanece en la misma línea: `metodo() {`, `if(condicion) {`, `final class Servicio {`.
- Después de abrir una clase, interfaz, trait o enum, dejar una línea vacía antes de la primera constante, propiedad o método. Mantener también una línea vacía antes de la llave de cierre cuando el bloque tiene contenido.
- No separar el operador de negación de su expresión: `if(!Utilities::isDefined($value)) {`.
- Todas las continuaciones se escriben sin separación después de la llave: `}elseif(condicion) {`, `}else {`, `}catch(Throwable $exception) {` y `}finally {`.
- Antes de iniciar una nueva condición, iteración, función local o bloque independiente debe existir una línea vacía. Las asignaciones consecutivas pueden permanecer juntas si forman una sola operación.
- Después de una consulta, colección o asignación multilínea, separar con una línea vacía la siguiente asignación o responsabilidad.
- Dentro de condiciones, iteraciones, funciones, `try` y `catch`, dejar una línea vacía después de la llave de apertura y otra antes de la llave de cierre:

```php
if($value === null) {

    return null;

}elseif(!Utilities::isDefined($value)) {

    procesar($value);

}
```

```php
try {

    ejecutarOperacion();

}catch(Throwable $exception) {

    reportar($exception);

}
```

- Agrupar imports por namespace, incluso si el grupo contiene actualmente una sola clase. Esto facilita añadir dependencias del mismo módulo sin multiplicar líneas:

```php
use AppModels\System\Operations\{ServiceFloor, ServiceSession, ServiceSessionItem, ServiceStation};
use AppServices\System\Sales\{SaleService};
```
- Usar cuatro espacios y nunca tabulaciones.
- Separar con una línea en blanco bloques que cumplen responsabilidades diferentes: validación, consulta, mutación y construcción de respuesta.
- Mantener juntas las instrucciones que forman una sola operación. No insertar líneas vacías entre llamadas estrechamente relacionadas.
- Dividir expresiones largas por argumentos o por cada etapa encadenada cuando mejora la lectura; no fragmentar expresiones cortas.
- Los nombres técnicos permanecen en inglés y los textos visibles para el usuario en español.
- No realizar sustituciones textuales globales de comillas. El transformador usa tokens PHP para proteger interpolaciones y secuencias de escape.

## Automatización

Aplicar formato:

```bash
composer format:php
```

Validar sin modificar:

```bash
composer format:php-check
composer check:php-syntax
```

`scripts/format-php-double-quotes.php` normaliza literales de forma semánticamente segura. `scripts/format-php-control-structures.php` normaliza imports individuales, condiciones, negaciones, continuaciones y el espacio interno de funciones, clases, interfaces, traits y enums. Pint agrupa imports compatibles y aplica el formato base según `pint.json`.
