<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma del Módulo de Empresas
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para los mensajes y
    | respuestas del módulo de Empresas. Estas pueden personalizarse
    | para adaptarse a los requisitos de su aplicación.
    |
    */

    // Mensajes de Éxito
    "created" => "Empresa creada exitosamente.",
    "updated" => "Empresa actualizada exitosamente.",
    "deleted" => "Empresa eliminada exitosamente.",

    // Mensajes de Error
    "not_found" => "Empresa no encontrada.",
    "not_implemented" => "Funcionalidad no implementada.",
    "create_failed" => "No se pudo crear la empresa.",
    "update_failed" => "No se pudo actualizar la empresa.",
    "delete_failed" => "No se pudo eliminar la empresa.",

    // Mensajes de Validación
    "internal_code_exists" => "El código interno ya está en uso para esta empresa.",

    // Mensajes de Excepción
    "exception_create" => "Error al crear la empresa: :message",
    "exception_update" => "Error al actualizar la empresa: :message",
    "exception_delete" => "Error al eliminar la empresa: :message",
];
