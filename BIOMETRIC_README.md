# Sistema Biométrico ZKTeco - Documentación Completa

## 📋 Tabla de Contenidos

1. [Descripción General](#descripción-general)
2. [Instalación](#instalación)
3. [Configuración](#configuración)
4. [Uso del Sistema](#uso-del-sistema)
5. [API Reference](#api-reference)
6. [Troubleshooting](#troubleshooting)

## 📖 Descripción General

Este sistema integra dispositivos biométricos ZKTeco (modelo K20 Pro) para el registro automático de asistencia de clientes mediante huella dactilar. La validación de la huella se realiza en el dispositivo físico, y el sistema solo recibe el ID del usuario asociado para procesar la asistencia.

### Características

- ✅ Gestión de dispositivos biométricos (CRUD completo)
- ✅ Registro de huellas de clientes asociadas a dispositivos
- ✅ Recepción automática de eventos del dispositivo
- ✅ Validación de membresías activas antes de registrar asistencia
- ✅ Soporte para check-in y check-out
- ✅ Interfaz de usuario moderna con Vue.js
- ✅ API pública para eventos del dispositivo
- ✅ Logging y manejo de errores

## 🚀 Instalación

### 1. Ejecutar Migraciones

```bash
php artisan migrate
```

Esto creará las siguientes tablas:
- `biometric_devices` - Dispositivos biométricos registrados
- `customer_biometric_fingerprints` - Asociación cliente-huella-dispositivo

**Nota**: El tipo "biometric" ya está incluido en el enum de `attendances.type` desde la migración inicial.

### 2. Verificar Menú

La entrada "Dispositivos Biométricos" se agregará automáticamente en la sección "Infraestructura" del menú del sistema.

## ⚙️ Configuración

### Estructura de Base de Datos

#### Tabla: `biometric_devices`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único del dispositivo |
| `company_id` | bigint | ID de la empresa |
| `branch_id` | bigint | ID de la sucursal |
| `name` | string | Nombre del dispositivo |
| `brand` | enum | Marca (ZKTeco) |
| `model` | enum | Modelo (K20 Pro) |
| `serial_number` | string | Número de serie (opcional) |
| `ip_address` | string | Dirección IP del dispositivo |
| `port` | integer | Puerto (default: 4370) |
| `device_id` | integer | ID del dispositivo (opcional) |
| `description` | text | Descripción (opcional) |
| `status` | enum | Estado (active/inactive) |

#### Tabla: `customer_biometric_fingerprints`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único del registro |
| `company_id` | bigint | ID de la empresa |
| `customer_id` | bigint | ID del cliente |
| `biometric_device_id` | bigint | ID del dispositivo |
| `device_user_id` | integer | ID del usuario en el dispositivo |
| `finger_index` | integer | Índice del dedo (0-9, default: 0) |
| `fingerprint_template` | text | Template de la huella (opcional) |
| `status` | enum | Estado (active/inactive) |

**Índice único**: `["biometric_device_id", "device_user_id", "finger_index"]`

### Modelos y Servicios

- **Modelos**: `BiometricDevice`, `CustomerBiometricFingerprint`
- **Servicios**: `BiometricDeviceService`, `BiometricDeviceConfigService`
- **Controladores**: `BiometricDeviceController`, `CustomerController`

## 📱 Uso del Sistema

### Flujo de Trabajo Completo

#### 1. Crear Dispositivo Biométrico

1. Acceder a **Infraestructura > Dispositivos Biométricos**
2. Hacer clic en "Agregar dispositivo"
3. Completar el formulario:
   - **Nombre**: Ej: "Lector Principal - Recepción"
   - **Sucursal**: Seleccionar la sucursal
   - **Marca**: ZKTeco (selección automática)
   - **Modelo**: K20 Pro (selección automática)
   - **IP**: La IP estática del dispositivo (Ej: 192.168.1.100)
   - **Puerto**: 4370 (puerto por defecto)
   - **Estado**: Activo
4. Guardar

#### 2. Registrar Cliente con Huella

1. Crear un cliente en el sistema (si no existe)
2. Después de crear el cliente, aparecerá un modal para registrar huella biométrica
3. En el modal:
   - Seleccionar el dispositivo biométrico
   - El sistema asignará automáticamente el próximo `device_user_id` disponible
   - Opcionalmente, especificar el dedo (default: 0 - Pulgar derecho)
4. Hacer clic en "Registrar huella"
5. **Importante**: Anotar el `device_user_id` asignado
6. Ir al dispositivo físico ZKTeco y registrar la huella del cliente con ese ID

#### 3. Configurar el Dispositivo ZKTeco K20 Pro

El dispositivo debe enviar eventos HTTP POST cuando se escanee una huella.

**Configuración del Webhook:**

- **URL**: `http://tu-servidor.com/{company_slug}/biometric_devices/receiveEvent`
- **Método**: POST
- **Content-Type**: application/json

**Parámetros a enviar:**

```json
{
    "user_id": 123,                    // REQUERIDO: ID del usuario en el dispositivo
    "timestamp": "2024-12-15 10:30:00", // OPCIONAL: Fecha y hora (default: now())
    "action": "checkin",                // OPCIONAL: "checkin" o "checkout" (default: "checkin")
    "device_id": 1                      // OPCIONAL: ID del dispositivo en el sistema
}
```

**Nota**: Si no se proporciona `device_id`, el sistema identificará el dispositivo por su dirección IP.

#### 4. Flujo de Asistencia Automática

1. **Cliente llega al gimnasio**
2. **Coloca su huella en el dispositivo ZKTeco**
3. **El dispositivo valida la huella localmente**
4. **El dispositivo envía evento HTTP POST al sistema**
5. **El sistema busca el cliente asociado al `device_user_id`**
6. **El sistema valida que tenga una membresía activa en la sucursal**
7. **El sistema registra la asistencia (ingreso o salida)**
8. **El sistema retorna respuesta al dispositivo**

## 🔌 API Reference

### Endpoint Público: Recibir Eventos

#### POST `/{company_slug}/biometric_devices/receiveEvent`

Recibe eventos del dispositivo biométrico cuando se escanea una huella.

**Headers:**
```
Content-Type: application/json
```

**Body (JSON):**
```json
{
    "user_id": 123,
    "timestamp": "2024-12-15 10:30:00",
    "action": "checkin",
    "device_id": 1
}
```

**Parámetros:**

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `user_id` | integer | Sí | ID del usuario en el dispositivo biométrico |
| `timestamp` | string | No | Fecha y hora del evento (formato: Y-m-d H:i:s). Si no se proporciona, usa la hora actual |
| `action` | string | No | Tipo de acción: "checkin" o "checkout" (default: "checkin") |
| `device_id` | integer | No | ID del dispositivo en el sistema. Si no se proporciona, se identifica por IP |

**Respuesta Exitosa (200):**
```json
{
    "bool": true,
    "msg": "¡Bienvenido, Juan Pérez! Disfruta tu rutina.",
    "action": "checkin",
    "customer": {
        "id": 1,
        "name": "Juan Pérez"
    }
}
```

**Errores Posibles:**

- **400**: Parámetros inválidos o faltantes
  ```json
  {
      "bool": false,
      "msg": "El parámetro 'user_id' es requerido."
  }
  ```

- **404**: Usuario o dispositivo no encontrado
  ```json
  {
      "bool": false,
      "msg": "Usuario no encontrado en el sistema. Verifique que la huella esté registrada correctamente."
  }
  ```

- **422**: Membresía no vigente o asistencia activa existente
  ```json
  {
      "bool": false,
      "msg": "Juan Pérez: No cuenta con una membresía vigente en la sucursal."
  }
  ```

### Endpoints del Sistema (Requieren Autenticación)

#### GET `/biometric_devices`
Lista de dispositivos biométricos del sistema.

#### GET `/biometric_devices/initParams`
Parámetros de inicialización para la interfaz.

#### GET `/biometric_devices/list`
Lista paginada de dispositivos con filtros.

#### POST `/biometric_devices`
Crear un nuevo dispositivo biométrico.

#### PATCH `/biometric_devices/{id}`
Actualizar un dispositivo existente.

#### DELETE `/biometric_devices/{id}`
Eliminar un dispositivo (soft delete).

#### POST `/customers/registerBiometricFingerprint/{id}`
Registrar la huella de un cliente en un dispositivo.

**Parámetros:**
- `biometric_device_id` (requerido): ID del dispositivo
- `device_user_id` (opcional): ID del usuario en el dispositivo (se asigna automáticamente si no se proporciona)
- `finger_index` (opcional): Índice del dedo (0-9, default: 0)

## 🐛 Troubleshooting

### El dispositivo no encuentra el cliente

**Posibles causas:**
- El cliente no tiene una huella registrada con el `device_user_id` correcto
- El dispositivo no está correctamente registrado en el sistema
- La IP del dispositivo no coincide con la configurada

**Solución:**
1. Verificar que el cliente tenga una huella registrada en el sistema
2. Verificar que el `device_user_id` en la petición coincida con el registrado
3. Verificar que el dispositivo esté activo en el sistema
4. Incluir el `device_id` en la petición si hay problemas con la identificación por IP

### Error "No cuenta con una membresía vigente"

**Posibles causas:**
- El cliente no tiene una membresía activa
- La membresía no está vigente en la sucursal del dispositivo
- Las fechas de inicio y fin de la membresía no son válidas

**Solución:**
1. Crear una venta asociada a una membresía para el cliente
2. Verificar que la membresía esté activa y vigente
3. Verificar que la sucursal del dispositivo coincida con la de la membresía

### El sistema no recibe eventos

**Posibles causas:**
- El dispositivo no puede hacer peticiones HTTP al servidor
- La URL del webhook es incorrecta
- El firewall bloquea las peticiones

**Solución:**
1. Verificar conectividad de red entre el dispositivo y el servidor
2. Verificar que la URL sea accesible desde la red del dispositivo
3. Revisar los logs del servidor web (Apache/Nginx)
4. Verificar configuración del firewall

### Verificar Logs

Los eventos se registran en:
```
storage/logs/laravel.log
```

Buscar entradas relacionadas con:
- "biometric"
- "Error processing biometric event"
- "Biometric device not found"
- "Biometric customer not found"

**Comando para monitorear logs:**
```bash
tail -f storage/logs/laravel.log | grep "biometric"
```

## 📝 Pruebas con cURL

### Test 1: Check-in exitoso
```bash
curl -X POST http://localhost/mi-empresa/biometric_devices/receiveEvent \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "action": "checkin",
    "device_id": 1
  }'
```

### Test 2: Check-out exitoso
```bash
curl -X POST http://localhost/mi-empresa/biometric_devices/receiveEvent \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "action": "checkout",
    "device_id": 1
  }'
```

### Test 3: Error - Usuario no encontrado
```bash
curl -X POST http://localhost/mi-empresa/biometric_devices/receiveEvent \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 99999,
    "action": "checkin",
    "device_id": 1
  }'
```

## ✅ Checklist de Implementación

### Base de Datos
- [x] Migración `biometric_devices`
- [x] Migración `customer_biometric_fingerprints`
- [x] Tipo "biometric" en enum `attendances.type`
- [x] Entrada de menú agregada

### Backend
- [x] Modelos Eloquent
- [x] Servicios de negocio
- [x] Controladores
- [x] Form Requests (validaciones)
- [x] Rutas API

### Frontend
- [x] Página de administración de dispositivos
- [x] Modal de registro de huella en formulario cliente
- [x] Integración con menú
- [x] Archivo JavaScript de montaje Vue

## 🎯 Estado del Proyecto

**✅ COMPLETO Y LISTO PARA PRODUCCIÓN**

Todos los componentes necesarios han sido implementados, probados y documentados. El sistema está listo para ser desplegado en un entorno de producción después de ejecutar las migraciones.

## 📚 Recursos Adicionales

- [Documentación de ZKTeco](https://www.zkteco.com/)
- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/)

---

**Nota**: Este sistema está diseñado para ser escalable. En el futuro se pueden agregar más modelos de ZKTeco y otras marcas de dispositivos biométricos.

