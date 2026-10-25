# 01 - Mi cuenta

## Objetivo

`Mi cuenta` permite que cada usuario autenticado actualice sus propios datos personales sin ingresar al mantenimiento administrativo de Colaboradores.

## Ubicación

El acceso pertenece al mundo `Administración`, dentro del grupo `Ajustes`. Su ruta principal es `account.index`.

## Datos editables

- nombre completo;
- correo electrónico, único dentro de la organización;
- teléfono;
- género;
- fecha de nacimiento.

El formulario no acepta cambios de empresa, perfil de acceso, estado, sucursales, almacenes ni cajas. El backend toma siempre el usuario autenticado y filtra explícitamente los campos admitidos.

## Cierre de sesión

El riel principal no utiliza un avatar desplegable en su parte inferior. Muestra un botón de cierre de sesión con icono de encendido, contraste destructivo moderado y tooltip propio. La acción conserva el formulario `POST` con protección CSRF.
