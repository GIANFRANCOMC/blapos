<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma del Módulo de Sucursales
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para los mensajes y
    | respuestas del módulo de Sucursales. Estas pueden personalizarse
    | para adaptarse a los requisitos de su aplicación.
    |
    */

    // Mensajes de Éxito
    "created" => "Sucursal creada exitosamente.",
    "updated" => "Sucursal actualizada exitosamente.",
    "deleted" => "Sucursal eliminada exitosamente.",

    // Mensajes de Error
    "not_found" => "Sucursal no encontrada.",
    "not_implemented" => "Funcionalidad no implementada.",
    "create_failed" => "No se pudo crear la sucursal.",
    "update_failed" => "No se pudo actualizar la sucursal.",
    "delete_failed" => "No se pudo eliminar la sucursal.",

    // Mensajes de Validación
    "internal_code_exists" => "El código interno ya está en uso para esta empresa.",

    // Mensajes de Excepción
    "exception_create" => "Error al crear la sucursal: :message",
    "exception_update" => "Error al actualizar la sucursal: :message",
    "exception_delete" => "Error al eliminar la sucursal: :message",
];
