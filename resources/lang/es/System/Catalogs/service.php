<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma del Módulo de Servicios
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para los mensajes y
    | respuestas del módulo de Servicios. Estas pueden personalizarse
    | para adaptarse a los requisitos de su aplicación.
    |
    */

    // Mensajes de Éxito
    "created" => "Servicio creado exitosamente.",
    "updated" => "Servicio actualizado exitosamente.",
    "deleted" => "Servicio eliminado exitosamente.",

    // Mensajes de Error
    "not_found" => "Servicio no encontrado.",
    "not_implemented" => "Funcionalidad no implementada.",
    "create_failed" => "No se pudo crear el servicio.",
    "update_failed" => "No se pudo actualizar el servicio.",
    "delete_failed" => "No se pudo eliminar el servicio.",

    // Mensajes de Validación
    "internal_code_exists" => "El código interno ya está en uso para esta empresa.",

    // Mensajes de Excepción
    "exception_create" => "Error al crear el servicio: :message",
    "exception_update" => "Error al actualizar el servicio: :message",
    "exception_delete" => "Error al eliminar el servicio: :message",
];
