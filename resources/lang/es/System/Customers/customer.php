<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Líneas de Idioma del Módulo de Clientes
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para los mensajes y
    | respuestas del módulo de Clientes. Estas pueden
    | personalizarse para adaptarse a los requisitos de su aplicación.
    |
    */

    // Mensajes de Éxito
    "created" => "Cliente creado correctamente.",
    "updated" => "Cliente editado correctamente.",
    "deleted" => "Cliente eliminado correctamente.",
    "retrieved" => "Cliente obtenido correctamente.",

    // Mensajes de Error
    "not_found" => "Cliente no encontrado.",
    "not_implemented" => "Funcionalidad no implementada.",
    "create_failed" => "No se ha podido crear el cliente.",
    "update_failed" => "No se ha podido editar el cliente.",
    "delete_failed" => "No se ha podido eliminar el cliente.",
    "retrieve_failed" => "No se ha podido obtener el cliente.",
    "delete_not_implemented" => "Funcionalidad de eliminación no implementada.",

    // Mensajes de Validación

    // Mensajes Generales
    "init_params_error" => "Error al obtener parámetros de inicialización.",
    "list_error" => "Error al obtener la lista de clientes.",

    // Mensajes de Excepción
    "exception_create" => "Error al crear el cliente: :message",
    "exception_update" => "Error al editar el cliente: :message",
    "exception_delete" => "Error al eliminar el cliente: :message",
    "exception_retrieve" => "Error al obtener el cliente: :message",
    "exception_list" => "Error al obtener la lista de clientes: :message",
];
