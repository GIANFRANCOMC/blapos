<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma del Módulo de Activos
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para los mensajes y
    | respuestas del módulo de Activos. Estas pueden personalizarse
    | para adaptarse a los requisitos de su aplicación.
    |
    */

    // Mensajes de Éxito
    "created" => "Activo creado exitosamente.",
    "updated" => "Activo actualizado exitosamente.",
    "deleted" => "Activo eliminado exitosamente.",

    // Mensajes de Error
    "not_found" => "Activo no encontrado.",
    "not_implemented" => "Funcionalidad no implementada.",
    "create_failed" => "No se pudo crear el activo.",
    "update_failed" => "No se pudo actualizar el activo.",
    "delete_failed" => "No se pudo eliminar el activo.",

    // Mensajes de Validación
    "internal_code_exists" => "El código interno ya está en uso para esta empresa.",

    // Mensajes de Excepción
    "exception_create" => "Error al crear el activo: :message",
    "exception_update" => "Error al actualizar el activo: :message",
    "exception_delete" => "Error al eliminar el activo: :message",
];
