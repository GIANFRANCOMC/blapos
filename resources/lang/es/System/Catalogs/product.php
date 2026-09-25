<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma del Módulo de Productos
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para los mensajes y
    | respuestas del módulo de Productos. Estas pueden personalizarse
    | para adaptarse a los requisitos de su aplicación.
    |
    */

    // Mensajes de Éxito
    "created" => "Producto agregado exitosamente.",
    "updated" => "Producto editado exitosamente.",
    "deleted" => "Producto eliminado exitosamente.",

    // Mensajes de Error
    "not_found" => "Producto no encontrado.",
    "not_implemented" => "Funcionalidad no implementada.",
    "create_failed" => "No se pudo crear el producto.",
    "update_failed" => "No se pudo actualizar el producto.",
    "delete_failed" => "No se pudo eliminar el producto.",

    // Mensajes de Validación
    "internal_code_exists" => "El código interno ya está en uso para esta empresa.",

    // Mensajes de Excepción
    "exception_create" => "Error al crear el producto: :message",
    "exception_update" => "Error al actualizar el producto: :message",
    "exception_delete" => "Error al eliminar el producto: :message",
];
