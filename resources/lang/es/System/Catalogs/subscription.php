<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma del Módulo de Suscripciones
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para los mensajes y
    | respuestas del módulo de Suscripciones. Estas pueden personalizarse
    | para adaptarse a los requisitos de su aplicación.
    |
    */

    // Mensajes de Éxito
    "created" => "Suscripción creada exitosamente.",
    "updated" => "Suscripción actualizada exitosamente.",
    "deleted" => "Suscripción eliminada exitosamente.",

    // Mensajes de Error
    "not_found" => "Suscripción no encontrada.",
    "not_implemented" => "Funcionalidad no implementada.",
    "create_failed" => "No se pudo crear la suscripción.",
    "update_failed" => "No se pudo actualizar la suscripción.",
    "delete_failed" => "No se pudo eliminar la suscripción.",

    // Mensajes de Validación
    "internal_code_exists" => "El código interno ya está en uso para esta empresa.",

    // Mensajes de Excepción
    "exception_create" => "Error al crear la suscripción: :message",
    "exception_update" => "Error al actualizar la suscripción: :message",
    "exception_delete" => "Error al eliminar la suscripción: :message",
];
