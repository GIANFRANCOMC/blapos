<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma del Módulo de Membresías
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para los mensajes y
    | respuestas del módulo de Membresías. Estas pueden personalizarse
    | para adaptarse a los requisitos de su aplicación.
    |
    */

    // Mensajes de Éxito
    "created" => "Membresía creada exitosamente.",
    "updated" => "Membresía actualizada exitosamente.",
    "deleted" => "Membresía eliminada exitosamente.",

    // Mensajes de Error
    "not_found" => "Membresía no encontrada.",
    "not_implemented" => "Funcionalidad no implementada.",
    "create_failed" => "No se pudo crear la membresía.",
    "update_failed" => "No se pudo actualizar la membresía.",
    "delete_failed" => "No se pudo eliminar la membresía.",

    // Mensajes de Validación
    "internal_code_exists" => "El código interno ya está en uso para esta empresa.",

    // Mensajes de Excepción
    "exception_create" => "Error al crear la membresía: :message",
    "exception_update" => "Error al actualizar la membresía: :message",
    "exception_delete" => "Error al eliminar la membresía: :message",
];
